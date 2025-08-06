<?php

namespace Tests\Feature;

use App\Actions\Leave\GetLeave;
use App\Models\LeaveApplication;
use App\Models\User;
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

    $getLeave = new GetLeave();
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

        $getLeave = new GetLeave();

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
}
