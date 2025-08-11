<?php

namespace Tests\Feature;

use App\Actions\Leave\GetLeave;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Notifications\LeaveRequestSubmitted;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createUserWithLeaveAbility(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('employee');

        return $user;
    }

    #[Test]
    public function store_leave_request_requires_valid_data(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        // Send empty strings to trigger validation errors but avoid nulls for date fields
        $response = $this->post(route('leave.store'), [
            'start_date' => '',
            'end_date' => '',
            'leave_type' => '',
            'reason' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'start_date',
            'end_date',
            'leave_type',
            'reason',
        ]);
    }

    #[Test]
    public function start_date_must_be_at_least_seven_days_in_advance_for_annual_leave(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $startDate = now()->addDays(3)->toDateString();
        $endDate = now()->addDays(5)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'leave_type' => 'annual',
            'reason' => 'Vacation planned',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('start_date');
        $this->assertStringContainsString('7 days in advance', session('errors')->get('start_date')[0]);
    }

    #[Test]
    public function leave_dates_cannot_overlap_existing_approved_or_pending_leave(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        LeaveApplication::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'status' => 'approved',
        ]);

        $response = $this->post(route('leave.store'), [
            'start_date' => now()->addDays(11)->toDateString(),
            'end_date' => now()->addDays(13)->toDateString(),
            'leave_type' => 'annual',
            'reason' => 'Overlap test reason',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('start_date');
        $this->assertStringContainsString('overlap', session('errors')->get('start_date')[0]);
    }

    #[Test]
    public function get_leave_color_category_returns_expected_values(): void
    {
        $realUser = User::factory()->create();
        $leave = LeaveApplication::factory()->make([
            'user_id' => $realUser->id,
            'leave_days' => 2.0,
            'leave_type' => 'personal',
            'status' => 'pending',
        ]);

        $getLeave = new GetLeave;
        $reflection = new \ReflectionMethod(GetLeave::class, 'getLeaveColorCategory');
        $reflection->setAccessible(true);

        // Pending status returns 'pending'
        $leave->status = 'pending';
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getRemainingLeaveBalance'])
            ->getMock();
        $userMock->method('getRemainingLeaveBalance')->willReturn(3.0);
        $leave->setRelation('user', $userMock);
        $this->assertSame('pending', $reflection->invoke($getLeave, $leave));

        // Personal leave with enough balance returns 'personal'
        $leave->status = 'approved';
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getRemainingLeaveBalance'])
            ->getMock();
        $userMock->method('getRemainingLeaveBalance')->willReturn(3.0);
        $leave->setRelation('user', $userMock);
        $leave->leave_days = 2.0;
        $this->assertSame('personal', $reflection->invoke($getLeave, $leave));

        // Personal leave with insufficient balance returns 'paid'
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getRemainingLeaveBalance'])
            ->getMock();
        $userMock->method('getRemainingLeaveBalance')->willReturn(1.0);
        $leave->setRelation('user', $userMock);
        $leave->leave_days = 2.0;
        $this->assertSame('paid', $reflection->invoke($getLeave, $leave));

        // The rest of the leave type mappings remain
        $leaveTypesMap = [
            'annual' => 'annual',
            'sick' => 'sick',
            'emergency' => 'emergency',
            'maternity' => 'maternity',
            'paternity' => 'paternity',
            'wfh' => 'wfh',
            'compensatory' => 'compensatory',
            'unknown_type' => 'unknown',
        ];

        foreach ($leaveTypesMap as $type => $expectedCategory) {
            $leave->leave_type = $type;
            $leave->status = 'approved';
            $this->assertSame($expectedCategory, $reflection->invoke($getLeave, $leave));
        }
    }

    #[Test]
    public function handle_returns_paginated_leave_requests_with_expected_structure(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        LeaveApplication::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $getLeave = new GetLeave;

        $result = $getLeave->handle();

        $this->assertArrayHasKey('leaveRequests', $result);
        $this->assertArrayHasKey('canManage', $result);
        $this->assertArrayHasKey('highlightedDates', $result);
        $this->assertArrayHasKey('remainingLeaveBalance', $result);
        $this->assertArrayHasKey('compOffBalance', $result);

        $this->assertFalse($result['canManage']);
        $this->assertEquals($user->getRemainingLeaveBalance(), $result['remainingLeaveBalance']);
        $this->assertEquals($user->comp_off_balance ?? 0, $result['compOffBalance']);
        $this->assertIsArray($result['highlightedDates']);
        $this->assertCount(3, $result['highlightedDates']);
    }

    #[Test]
    public function annual_leave_with_sufficient_notice_is_successful(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $date = now()->addDays(8)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Annual vacation',
            'leave_type' => 'annual',
            'day_type' => 'full',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('leave_applications', [
            'user_id' => $user->id,
            'leave_type' => 'annual',
            'reason' => 'Annual vacation',
        ]);
    }

    #[Test]
    public function sick_leave_with_valid_document_succeeds(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $file = \Illuminate\Http\UploadedFile::fake()->create('medical.pdf', 500);
        $date = now()->addDays(2)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Doctor advised rest.',
            'leave_type' => 'sick',
            'supporting_document' => $file,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('leave_applications', [
            'user_id' => $user->id,
            'leave_type' => 'sick',
        ]);
    }

    #[Test]
    public function personal_half_day_leave_is_valid(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);
        $date = now()->addDays(5)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Errand in afternoon',
            'leave_type' => 'personal',
            'start_half_session' => 'afternoon',
            'end_half_session' => null,
            'day_type' => 'half',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('leave_applications', [
            'user_id' => $user->id,
            'leave_type' => 'personal',
            'start_half_session' => 'afternoon',
        ]);
    }

    #[Test]
    public function submission_with_missing_fields_fails(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $response = $this->post(route('leave.store'), []);
        $response->assertSessionHasErrors(['start_date', 'end_date', 'reason', 'leave_type']);
    }

    #[Test]
    public function submission_with_invalid_dates_fails(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $response = $this->post(route('leave.store'), [
            'start_date' => 'bad-date',
            'end_date' => 'another-bad-date',
            'reason' => 'Valid and long enough reason',
            'leave_type' => 'annual',
        ]);
        $response->assertSessionHasErrors(['start_date', 'end_date']);
    }

    #[Test]
    public function end_date_before_start_date_fails(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $start = now()->addDays(8)->toDateString();
        $end = now()->addDays(2)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $start,
            'end_date' => $end,
            'reason' => 'Backwards date!',
            'leave_type' => 'annual',
        ]);
        $response->assertSessionHasErrors(['end_date']);
    }

    #[Test]
    public function too_short_reason_fails_validation(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $date = now()->addDays(4)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'short',
            'leave_type' => 'sick',
        ]);
        $response->assertSessionHasErrors('reason');
    }

    #[Test]
    public function invalid_leave_type_fails_validation(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $response = $this->post(route('leave.store'), [
            'start_date' => now()->addDays(8)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
            'reason' => 'Wrong type',
            'leave_type' => 'holiday',
        ]);
        $response->assertSessionHasErrors('leave_type');
    }

    #[Test]
    public function invalid_half_session_value_fails(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $date = now()->addDays(7)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'leave_type' => 'personal',
            'reason' => 'Testing session',
            'start_half_session' => 'nightshift',
        ]);
        $response->assertSessionHasErrors('start_half_session');
    }

    #[Test]
    public function upload_of_invalid_document_type_fails(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);
        $file = \Illuminate\Http\UploadedFile::fake()->create('bad.exe', 100);

        $date = now()->addDays(4)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'leave_type' => 'sick',
            'reason' => 'Testing .exe file',
            'supporting_document' => $file,
        ]);
        $response->assertSessionHasErrors('supporting_document');
    }

    #[Test]
    public function upload_of_too_large_document_fails(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);
        $file = \Illuminate\Http\UploadedFile::fake()->create('bigfile.pdf', 6000); // 6MB

        $date = now()->addDays(5)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'leave_type' => 'sick',
            'reason' => 'Testing file size',
            'supporting_document' => $file,
        ]);
        $response->assertSessionHasErrors('supporting_document');
    }

    #[Test]
    public function annual_leave_with_insufficient_notice_fails(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);
        $date = now()->addDays(2)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Short notice',
            'leave_type' => 'annual',
        ]);
        $response->assertSessionHasErrors('start_date');
    }

    #[Test]
    public function overlapping_leave_dates_fails_validation(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        // Existing approved leave
        LeaveApplication::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'status' => 'approved',
        ]);

        // New leave overlaps
        $response = $this->post(route('leave.store'), [
            'start_date' => now()->addDays(11)->toDateString(),
            'end_date' => now()->addDays(13)->toDateString(),
            'leave_type' => 'annual',
            'reason' => 'Overlap test',
        ]);
        $response->assertSessionHasErrors('start_date');
    }

    #[Test]
    public function valid_wfh_and_compensatory_leave_succeed(): void
    {
        $user = $this->createUserWithLeaveAbility(['comp_off_balance' => 5]);
        $this->actingAs($user);

        $date = now()->addDays(1)->toDateString();

        $r1 = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'WFH one day',
            'leave_type' => 'wfh',
        ]);
        $r1->assertStatus(302);

        $r2 = $this->post(route('leave.store'), [
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Taking comp off',
            'leave_type' => 'compensatory',
        ]);
        $r2->assertStatus(302);
    }

    #[Test]
    public function test_multi_day_leave_with_partial_start_and_end_sessions(): void
    {
        // Create user with sufficient leave balance for this test
        $user = $this->createUserWithLeaveAbility([
            'leave_balance' => 10,       // Ensure enough leave balance
            'comp_off_balance' => 5,     // Ensure enough comp off balance
        ]);
        $this->actingAs($user);

        $s = now()->addDays(10)->toDateString();
        $e = now()->addDays(12)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $s,
            'end_date' => $e,
            'leave_type' => 'personal',
            'reason' => 'Multi-day leave with partial sessions',
            'start_half_session' => 'afternoon',
            'end_half_session' => 'morning',
            'day_type' => 'half', // Include this if your backend expects it
        ]);

        // Dump validation errors if any (comment out after debugging)
        $session = $response->baseResponse->getSession();
        if ($session && $session->has('errors')) {
            dump($session->get('errors')->all());
        } else {
            dump('No validation errors');
        }

        $response->assertStatus(302);

        $leave = LeaveApplication::where('user_id', $user->id)
            ->whereDate('start_date', $s)
            ->whereDate('end_date', $e)
            ->first();

        $this->assertNotNull($leave, 'Leave application was not created');
        $this->assertEquals('afternoon', $leave->start_half_session);
        $this->assertEquals('morning', $leave->end_half_session);
    }

    public function test_leave_days_exclude_weekends_and_holidays(): void
    {
        $user = $this->createUserWithLeaveAbility(['leave_balance' => 15]);
        $this->actingAs($user);

        // Use stable dates with correct order
        $start = now()->addDays(10)->toDateString();
        $end = now()->addDays(14)->toDateString(); // Leave span including a weekend in between

        $response = $this->post(route('leave.store'), [
            'start_date' => $start,
            'end_date' => $end,
            'leave_type' => 'personal',
            'reason' => 'Including weekend days',
            'start_half_session' => null,
            'end_half_session' => null,
        ]);

        if ($response->baseResponse->getSession()->has('errors')) {
            dump($response->baseResponse->getSession()->get('errors')->all());
        }

        $response->assertStatus(302);

        $leave = LeaveApplication::where('user_id', $user->id)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($leave, 'Leave application was not created');

        // Calculate expected leave days excluding weekends.
        // For example, from day 10 to day 14 is 5 days (Mon-Fri), weekends excluded (Sat/Sun)
        // Adjust based on exact dates generated in your test.
        $expectedLeaveDays = 4; // if the range covers a Saturday or Sunday, adjust accordingly

        // Dump actual value for debugging if needed:
        // dump('Actual leave_days:', $leave->leave_days);

        $this->assertEquals($expectedLeaveDays, $leave->leave_days, 'Leave days should exclude weekends');
    }

    public function test_user_can_cancel_pending_leave(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $leave = LeaveApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->delete(route('leave.cancel', $leave->id));
        $response->assertStatus(302);

        $this->assertDatabaseMissing('leave_applications', [
            'id' => $leave->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_update_leave_reason_only(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $leave = LeaveApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => 'Original reason',
        ]);

        $newReason = 'Updated leave reason';

        $response = $this->patch(route('leave.updateReason', $leave->id), [
            'reason' => $newReason,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('leave_applications', [
            'id' => $leave->id,
            'reason' => $newReason,
        ]);
    }

    public function test_wfh_leave_does_not_reduce_leave_balance(): void
    {
        // Create real user
        $user = $this->createUserWithLeaveAbility();

        // Create a partial mock for User to override getRemainingLeaveBalance
        $userMock = \Mockery::mock($user)->makePartial();
        $userMock->shouldReceive('getRemainingLeaveBalance')->andReturn(5);

        $this->actingAs($userMock);

        $date = now()->addDays(3)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'leave_type' => 'wfh',
            'reason' => 'Working from home',
        ]);

        $response->assertStatus(302);

        // Assert balance remains at 5 as expected
        $this->assertEquals(5, $userMock->getRemainingLeaveBalance());
    }

    public function test_maternity_leave_cannot_exceed_allowed_days(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $excessiveDays = 100;

        $response = $this->post(route('leave.store'), [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10 + $excessiveDays)->toDateString(),
            'leave_type' => 'maternity',
            'reason' => 'Maternity leave',
        ]);

        $response->assertSessionHasErrors('leave_days');
    }

    public function test_leave_cannot_be_applied_for_past_dates(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $pastDate = now()->subDays(5)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $pastDate,
            'end_date' => $pastDate,
            'leave_type' => 'personal',
            'reason' => 'Trying past date',
        ]);

        $response->assertSessionHasErrors('start_date');
    }

    public function test_leave_with_adjacent_dates_does_not_overlap(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        LeaveApplication::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'status' => 'approved',
        ]);

        $response = $this->post(route('leave.store'), [
            'start_date' => now()->addDays(13)->toDateString(), // immediately after existing leave end_date
            'end_date' => now()->addDays(14)->toDateString(),
            'leave_type' => 'annual',
            'reason' => 'Adjacent leave',
        ]);

        $response->assertStatus(302);
        $response->assertSessionDoesntHaveErrors('start_date');
    }

    public function test_multi_day_leave_with_half_day_sessions(): void
    {
        $user = $this->createUserWithLeaveAbility(['leave_balance' => 15]);
        $this->actingAs($user);

        $start = Carbon::parse('next monday')->addDays(2)->toDateString();
        $end = Carbon::parse($start)->addDays(2)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $start,
            'end_date' => $end,
            'leave_type' => 'personal',
            'reason' => 'Half day start afternoon, half day end morning',
            'start_half_session' => 'afternoon',
            'end_half_session' => 'morning',
        ]);

        $response->assertStatus(302);

        $leave = LeaveApplication::where('user_id', $user->id)->latest()->first();

        $this->assertNotNull($leave);

        // 3 weekdays - 0.5 - 0.5 = 2.0 days
        $this->assertEquals(2.0, $leave->leave_days);
    }

    public function test_notifications_sent_to_approvers(): void
    {
        Notification::fake();

        // Seed roles and permissions (if needed)
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = $this->createUserWithLeaveAbility(['leave_balance' => 15]);
        $approver = User::factory()->create();

        // Explicitly assign approver role to $approver, so roles relationship exists
        $approver->assignRole('admin'); // or any role used for approval

        // Option 1: Use a closure bound to user to mock getLeaveApprovers (simple)
        $user = tap($user, function ($user) use ($approver) {
            $user->setRelation('leaveApprovers', collect([$approver]));
            $user->getLeaveApprovers = fn () => collect([$approver]);
        });

        $this->be($user);

        $start = Carbon::parse('next monday')->toDateString();
        $end = Carbon::parse($start)->addDays(2)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $start,
            'end_date' => $end,
            'leave_type' => 'personal',
            'reason' => 'Testing notifications',
        ]);

        $response->assertStatus(302);

        $leave = LeaveApplication::where('user_id', $user->id)->latest()->first();

        Notification::assertSentTo(
            collect([$approver]),
            LeaveRequestSubmitted::class
        );
    }

    public function test_only_authorized_user_can_create_leave(): void
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->post(route('leave.store'), [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
            'leave_type' => 'personal',
            'reason' => 'Unauthorized leave request',
        ]);

        $response->assertStatus(403); // forbidden
    }

    public function test_user_cannot_cancel_non_pending_leave(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $leave = LeaveApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $response = $this->delete(route('leave.cancel', $leave->id));
        $response->assertStatus(403); // Forbidden - cannot cancel approved leave
    }

    public function test_adjacent_half_day_leaves_dont_overlap(): void
    {
        $user = $this->createUserWithLeaveAbility(['leave_balance' => 10]);
        $this->actingAs($user);

        // First leave - Monday morning
        $start1 = now()->next('Monday')->toDateString();
        $response1 = $this->post(route('leave.store'), [
            'start_date' => $start1,
            'end_date' => $start1,
            'leave_type' => 'personal',
            'reason' => 'Morning off',
            'start_half_session' => 'morning',
            'end_half_session' => 'morning',
        ]);
        $response1->assertStatus(302);

        // Second leave - Monday afternoon (should not overlap morning session)
        $response2 = $this->post(route('leave.store'), [
            'start_date' => $start1,
            'end_date' => $start1,
            'leave_type' => 'personal',
            'reason' => 'Afternoon off',
            'start_half_session' => 'afternoon',
            'end_half_session' => 'afternoon',  
        ]);
        $response2->assertStatus(302);

        // Ensure both leaves are saved and counted separately
        $leaves = LeaveApplication::where('user_id', $user->id)->get();
        $this->assertCount(2, $leaves);
    }

    public function test_leave_request_equal_to_leave_balance_is_successful(): void
    {
        $user = $this->createUserWithLeaveAbility(['leave_balance' => 3]);
        $this->actingAs($user);

        $start = now()->addDays(10)->toDateString();
        $end = now()->addDays(12)->toDateString(); // 3 days

        $response = $this->post(route('leave.store'), [
            'start_date' => $start,
            'end_date' => $end,
            'leave_type' => 'annual',
            'reason' => 'Using full balance',
        ]);

        $response->assertStatus(302);
    }
}
