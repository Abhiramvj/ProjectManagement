<?php

namespace Tests\Feature;

use App\Actions\Leave\GetLeave;
use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'day_type' => 'full', // ADDED: day_type
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
            'day_type' => 'full', // ADDED: Ensure existing leave has day_type
        ]);

        $response = $this->post(route('leave.store'), [
            'start_date' => now()->addDays(11)->toDateString(),
            'end_date' => now()->addDays(13)->toDateString(),
            'leave_type' => 'annual',
            'reason' => 'Overlap test reason',
            'day_type' => 'full', // ADDED: day_type
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('start_date');
        $this->assertStringContainsString('overlap', session('errors')->get('start_date')[0]);
    }

    #[Test]
    public function test_get_leave_color_category()
    {
        $getLeave = new GetLeave;
        $reflection = new \ReflectionMethod($getLeave, 'getLeaveColorCategory');
        $reflection->setAccessible(true);

        $leave = new LeaveApplication;

        // Pending status should return 'pending'
        $leave->status = 'pending';
        $leave->leave_type = 'annual';
        $this->assertSame('pending', $reflection->invoke($getLeave, $leave));

        // Annual leave
        $leave->status = 'approved';
        $leave->leave_type = 'annual';
        $this->assertSame('annual', $reflection->invoke($getLeave, $leave));

        // Sick leave
        $leave->leave_type = 'sick';
        $this->assertSame('sick', $reflection->invoke($getLeave, $leave));

        // Personal leave
        $leave->leave_type = 'personal';
        $this->assertSame('personal', $reflection->invoke($getLeave, $leave));

        // Emergency leave
        $leave->leave_type = 'emergency';
        $this->assertSame('emergency', $reflection->invoke($getLeave, $leave));

        // Maternity leave
        $leave->leave_type = 'maternity';
        $this->assertSame('maternity', $reflection->invoke($getLeave, $leave));

        // Paternity leave
        $leave->leave_type = 'paternity';
        $this->assertSame('paternity', $reflection->invoke($getLeave, $leave));

        // WFH leave
        $leave->leave_type = 'wfh';
        $this->assertSame('wfh', $reflection->invoke($getLeave, $leave));

        // Compensatory leave
        $leave->leave_type = 'compensatory';
        $this->assertSame('compensatory', $reflection->invoke($getLeave, $leave));

        // Unknown type
        $leave->leave_type = 'randomtype';
        $this->assertSame('unknown', $reflection->invoke($getLeave, $leave));
    }

    #[Test]
    public function test_handle_returns_paginated_leave_requests_with_expected_structure(): void
    {
        $user = $this->createUserWithLeaveAbility(['leave_balance' => 11.5]);
        $this->actingAs($user);

        // Create 3 leave applications
        LeaveApplication::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'day_type' => 'full',
        ]);

        $getLeave = new GetLeave;
        $result = $getLeave->handle();

        // Basic structure checks
        $this->assertArrayHasKey('leaveRequests', $result);
        $this->assertArrayHasKey('canManage', $result);
        $this->assertArrayHasKey('highlightedDates', $result);
        $this->assertArrayHasKey('remainingLeaveBalance', $result);
        $this->assertArrayHasKey('compOffBalance', $result);

        $this->assertFalse($result['canManage']);
        $this->assertEquals($user->leave_balance, $result['remainingLeaveBalance']);
        $this->assertEquals($user->comp_off_balance ?? 0, $result['compOffBalance']);

        // 'leaveRequests' should be a LengthAwarePaginator
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result['leaveRequests']);
        $this->assertCount(3, $result['leaveRequests']); // the paginated items count

        // 'highlightedDates' includes leave events + holidays, so check at least the leave events are present
        $leaveDates = LeaveApplication::where('user_id', $user->id)->pluck('start_date')->map->toDateString()->all();

        $highlightedLeaveDates = collect($result['highlightedDates'])
            ->whereIn('start', $leaveDates)
            ->pluck('start')
            ->all();

        $this->assertEqualsCanonicalizing($leaveDates, $highlightedLeaveDates);
    }

    #[Test]
    public function test_annual_leave_with_sufficient_notice_is_successful(): void
    {
        $user = $this->createUserWithLeaveAbility(['leave_balance' => 10]);
        $this->actingAs($user);

        $date = now()->addDays(8)->format('Y-m-d');

        $response = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Annual vacation',
            'leave_type' => 'annual',
            'day_type' => 'full',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leave_applications', [
            'user_id' => $user->id,
            'leave_type' => 'annual',
            'reason' => 'Annual vacation',
            'status' => 'pending',
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
            'day_type' => 'full', // ADDED: day_type
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('leave_applications', [
            'user_id' => $user->id,
            'leave_type' => 'sick',
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
            'reason' => 'shrt', // 4 characters, < min:5
            'leave_type' => 'sick',
            'day_type' => 'full',
        ]);

        $response->assertSessionHasErrors(['reason']);
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
            'reason' => 'Short', // 5 chars, passes validation
            'leave_type' => 'annual',
            'day_type' => 'full', // must include this
        ]);

        $response->assertSessionHasErrors(['start_date']);
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
            'day_type' => 'full', // required
            'leave_type' => 'annual', // optional but good to include
            'reason' => 'Existing leave',
        ]);

        // New leave overlaps
        $response = $this->post(route('leave.store'), [
            'start_date' => now()->addDays(11)->toDateString(),
            'end_date' => now()->addDays(13)->toDateString(),
            'leave_type' => 'annual',
            'day_type' => 'full', // must include
            'reason' => 'Overlap test', // >=5 chars
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

        // Use fixed dates for clarity
        $start = Carbon::parse('next monday'); // e.g., Monday
        $end = (clone $start)->addDays(4);     // Friday (5 days)

        $response = $this->post(route('leave.store'), [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'leave_type' => 'personal',
            'reason' => 'Including weekend days',
            'day_type' => 'full', // important
        ]);

        // Debug validation errors if any
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

        // Calculate expected leave days programmatically, skipping weekends
        $current = clone $start;
        $expectedLeaveDays = 0;
        while ($current->lte($end)) {
            if (! in_array($current->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $expectedLeaveDays++;
            }
            $current->addDay();
        }

        $this->assertEquals(
            $expectedLeaveDays,
            $leave->leave_days,
            'Leave days should exclude weekends'
        );
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

    // public function test_maternity_leave_cannot_exceed_allowed_days(): void
    // {
    //     $user = $this->createUserWithLeaveAbility();
    //     $this->actingAs($user);

    //     $excessiveDays = 100;

    //     $response = $this->post(route('leave.store'), [
    //         'start_date' => now()->addDays(10)->toDateString(),
    //         'end_date' => now()->addDays(10 + $excessiveDays)->toDateString(),
    //         'leave_type' => 'maternity',
    //         'reason' => 'Maternity leave',
    //     ]);

    //     $response->assertSessionHasErrors('leave_days');
    // }

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

        $date = now()->addDays(3)->toDateString(); // any future date

        // First leave - Monday morning
        $response1 = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'leave_type' => 'personal',
            'reason' => 'Morning off',
            'day_type' => 'half',
            'start_half_session' => 'morning',
            'end_half_session' => 'morning',
        ]);
        $response1->assertStatus(302);

        // Second leave - same day afternoon (should NOT overlap)
        $response2 = $this->post(route('leave.store'), [
            'start_date' => $date,
            'end_date' => $date,
            'leave_type' => 'personal',
            'reason' => 'Afternoon off',
            'day_type' => 'half',
            'start_half_session' => 'afternoon',
            'end_half_session' => 'afternoon',
        ]);
        $response2->assertStatus(302);

        // Check both leaves exist
        $leaves = LeaveApplication::where('user_id', $user->id)
            ->where('start_date', $date)
            ->get();

        $this->assertCount(2, $leaves);

        // Ensure sessions are correct
        $this->assertTrue($leaves->contains(fn ($l) => $l->start_half_session === 'morning'));
        $this->assertTrue($leaves->contains(fn ($l) => $l->start_half_session === 'afternoon'));
    }

    public function test_non_admin_cannot_apply_leave_for_past_dates(): void
    {
        $user = $this->createUserWithLeaveAbility();
        $this->actingAs($user);

        $pastDate = now()->subDays(3)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $pastDate,
            'end_date' => $pastDate,
            'leave_type' => 'personal',
            'reason' => 'Trying past date',
            'day_type' => 'full', // <-- use the valid value
        ]);

        // Non-admin cannot apply for past date
        $response->assertSessionHasErrors('start_date');

        // Ensure no leave is created
        $this->assertDatabaseMissing('leave_applications', [
            'user_id' => $user->id,
            'start_date' => $pastDate,
        ]);
    }

    public function test_admin_can_apply_sick_leave_for_self_in_past(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $pastDate = now()->subDays(2)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $pastDate,
            'end_date' => $pastDate,
            'leave_type' => 'sick', // Allowed
            'reason' => 'Admin sick leave in past',
            'day_type' => 'full',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('leave_applications', [
            'user_id' => $admin->id,
            'start_date' => $pastDate,
            'leave_type' => 'sick',
            'reason' => 'Admin sick leave in past',
        ]);
    }

    public function test_admin_cannot_apply_annual_or_personal_leave_for_self_in_past(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $pastDate = now()->subDays(2)->toDateString();

        foreach (['annual', 'personal'] as $type) {
            $response = $this->post(route('leave.store'), [
                'start_date' => $pastDate,
                'end_date' => $pastDate,
                'leave_type' => $type,
                'reason' => 'Admin '.$type.' leave in past',
                'day_type' => 'full',
            ]);

            $response->assertSessionHasErrors('start_date');

            $this->assertDatabaseMissing('leave_applications', [
                'user_id' => $admin->id,
                'start_date' => $pastDate,
                'leave_type' => $type,
            ]);
        }
    }
}
