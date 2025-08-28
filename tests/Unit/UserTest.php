<?php

namespace Tests\Unit;

use App\Models\LeaveApplication;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up common roles before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Create roles that the User model's logic depends on
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'hr', 'guard_name' => 'web']);
    }

    #[Test]
    public function avatar_url_returns_storage_url_when_image_is_set(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['image' => 'avatars/my-avatar.jpg']);

        $avatarUrl = $user->avatar_url;

        $this->assertEquals(Storage::url('avatars/my-avatar.jpg'), $avatarUrl);
    }

    #[Test]
    public function avatar_url_returns_ui_avatars_url_when_image_is_null(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'image' => null]);

        $avatarUrl = $user->avatar_url;

        $this->assertEquals('https://ui-avatars.com/api/?name=John+Doe&background=random', $avatarUrl);
    }

    #[Test]
    public function a_user_has_a_parent_manager(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create(['parent_id' => $manager->id]);

        $this->assertInstanceOf(User::class, $employee->parent);
        $this->assertEquals($manager->id, $employee->parent->id);
    }

    #[Test]
    public function it_correctly_calculates_remaining_leave_balance(): void
    {
        $user = User::factory()->create(['leave_balance' => 25]);

        LeaveApplication::factory()->for($user)->create(['status' => 'approved', 'leave_days' => 5, 'start_date' => now()]); // Counted
        LeaveApplication::factory()->for($user)->create(['status' => 'approved', 'leave_days' => 3, 'start_date' => now()]); // Counted
        LeaveApplication::factory()->for($user)->create(['status' => 'pending', 'leave_days' => 2, 'start_date' => now()]); // Not counted here
        LeaveApplication::factory()->for($user)->create(['status' => 'approved', 'leave_days' => 4, 'start_date' => now()->subYear()]); // Not counted (last year)

        $remainingBalance = $user->getRemainingLeaveBalance();

        $this->assertEquals(17, $remainingBalance); // 25 - 5 - 3 = 17
    }

    #[Test]
    public function it_correctly_calculates_used_leave_days_for_current_year(): void
    {
        $user = User::factory()->create();
        LeaveApplication::factory()->for($user)->create(['status' => 'approved', 'leave_days' => 5, 'start_date' => now()]);
        LeaveApplication::factory()->for($user)->create(['status' => 'approved', 'leave_days' => 3, 'start_date' => now()]);
        LeaveApplication::factory()->for($user)->create(['status' => 'pending', 'leave_days' => 2, 'start_date' => now()]);

        $this->assertEquals(8, $user->getUsedLeaveDays());
    }

    #[Test]
    public function it_gathers_comprehensive_leave_statistics(): void
    {
        $user = User::factory()->create(['leave_balance' => 22]);
        LeaveApplication::factory()->for($user)->count(2)->create(['status' => 'approved', 'leave_days' => 5, 'start_date' => now()]); // 10 used days
        LeaveApplication::factory()->for($user)->count(3)->create(['status' => 'pending', 'leave_days' => 2, 'start_date' => now()]);  // 6 pending days
        LeaveApplication::factory()->for($user)->create(['status' => 'rejected', 'leave_days' => 4, 'start_date' => now()]);

        $stats = $user->getLeaveStatistics();

        $this->assertEquals(22, $stats['total_balance']);
        $this->assertEquals(10, $stats['used_days']);
        $this->assertEquals(6, $stats['pending_days']);
        $this->assertEquals(12, $stats['remaining_balance']); // 22 - 10
        $this->assertEquals(6, $stats['available_balance']);  // 22 - 10 - 6
        $this->assertEquals(2, $stats['approved_applications']);
        $this->assertEquals(3, $stats['pending_applications']);
        $this->assertEquals(1, $stats['rejected_applications']);
    }

    #[Test]
    public function is_manager_of_returns_true_for_direct_and_indirect_subordinates(): void
    {
        $manager = User::factory()->create();
        $subordinate = User::factory()->create(['parent_id' => $manager->id]);
        $grandSubordinate = User::factory()->create(['parent_id' => $subordinate->id]);
        $unrelatedUser = User::factory()->create();

        $this->assertTrue($manager->isManagerOf($subordinate));
        $this->assertTrue($manager->isManagerOf($grandSubordinate));
        $this->assertFalse($manager->isManagerOf($unrelatedUser));
        $this->assertFalse($subordinate->isManagerOf($manager));
    }

    #[Test]
    public function get_all_subordinates_returns_a_flat_collection_of_all_reports(): void
    {
        $manager = User::factory()->create();
        $child1 = User::factory()->create(['parent_id' => $manager->id]);
        $child2 = User::factory()->create(['parent_id' => $manager->id]);
        $grandchild1 = User::factory()->create(['parent_id' => $child1->id]);

        $subordinates = $manager->getAllSubordinates();

        $this->assertCount(3, $subordinates);
        $this->assertTrue($subordinates->contains('id', $child1->id));
        $this->assertTrue($subordinates->contains('id', $child2->id));
        $this->assertTrue($subordinates->contains('id', $grandchild1->id));
    }

    #[Test]
    public function test_user_with_manage_leave_permission_can_approve_leave(): void
{
    // Create a user and give them the role/permission
    $manager = User::factory()->create();
    $manager->assignRole('admin'); // ensure role has 'manage leave applications' permission

    // Create an employee
    $employee = User::factory()->create();

    // Make sure the manager has the permission
    $this->assertTrue($manager->can('manage leave applications'));

    // Assert they can approve the employee's leave
    $this->assertTrue($manager->canApproveLeaveFor($employee));
}


    #[Test]
    public function admin_or_hr_can_approve_leave_for_any_user(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $hr = User::factory()->create()->assignRole('hr');
        $employee = User::factory()->create();

        $this->assertTrue($admin->canApproveLeaveFor($employee));
        $this->assertTrue($hr->canApproveLeaveFor($employee));
    }

    #[Test]
    public function unrelated_user_cannot_approve_leave(): void
    {
        $unrelatedUser = User::factory()->create();
        $employee = User::factory()->create();

        $this->assertFalse($unrelatedUser->canApproveLeaveFor($employee));
    }

    #[Test]
    public function it_calculates_task_completion_rate(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Use the correct relationship name 'assignedTo' to link the task to the user.
        Task::factory()->for($user, 'assignedTo')->create(['status' => 'completed']); // <-- THE FIX
        Task::factory()->for($user, 'assignedTo')->create(['status' => 'completed']); // <-- THE FIX
        Task::factory()->for($user, 'assignedTo')->create(['status' => 'in_progress']); // <-- THE FIX
        Task::factory()->for($user, 'assignedTo')->create(['status' => 'pending']); // <-- THE FIX

        // Act
        $completionRate = $user->getTaskCompletionRate();

        // Assert
        $this->assertEquals(50.0, $completionRate);
    }

    #[Test]
    public function task_completion_rate_is_zero_when_no_tasks_exist(): void
    {
        $user = User::factory()->create();
        $this->assertEquals(0, $user->getTaskCompletionRate());
    }

    #[Test]
    public function it_calculates_current_month_hours(): void
    {
        $user = User::factory()->create();
        TimeLog::factory()->for($user)->create(['hours_worked' => 8, 'work_date' => now()]);
        TimeLog::factory()->for($user)->create(['hours_worked' => 5.5, 'work_date' => now()]);
        TimeLog::factory()->for($user)->create(['hours_worked' => 10, 'work_date' => now()->subMonths(2)]); // Should not be counted

        $this->assertEquals(13.5, $user->getCurrentMonthHours());
    }

    #[Test]
    public function it_checks_if_user_is_active_based_on_recent_time_logs(): void
    {
        $activeUser = User::factory()->create();
        TimeLog::factory()->for($activeUser)->create(['work_date' => now()->subDays(3)]);

        $inactiveUser = User::factory()->create();
        TimeLog::factory()->for($inactiveUser)->create(['work_date' => now()->subDays(10)]);

        $userWithNoLogs = User::factory()->create();

        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($inactiveUser->isActive());
        $this->assertFalse($userWithNoLogs->isActive());
    }

    #[Test]
    public function a_user_model_correctly_calculates_all_its_derived_attributes_and_relations(): void
    {
        // =================================================================
        // 1. ARRANGE: Create a complex, realistic scenario for one user.
        // =================================================================

        // Freeze time to make date-based calculations predictable.
        Carbon::setTestNow(now());
        Storage::fake('public');

        // --- Create a User Hierarchy ---
        $manager = User::factory()->create();
        $adminUser = User::factory()->create()->assignRole('admin');
        $otherUser = User::factory()->create();

        // This is our main test subject, "Jane Doe".
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'parent_id' => $manager->id, // Jane reports to the manager
            'leave_balance' => 25,     // Jane has 25 days of leave
            'image' => 'avatars/jane.jpg',
        ]);

        $subordinate = User::factory()->create(['parent_id' => $user->id]); // Jane manages this user

        // --- Create Tasks for Jane ---
        // 4 total tasks, 3 are completed = 75% completion rate.
        Task::factory()->for($user, 'assignedTo')->count(3)->create(['status' => 'completed']);
        Task::factory()->for($user, 'assignedTo')->create(['status' => 'in_progress']);

        // --- Create Time Logs for Jane ---
        // 140 hours this month, 20 last month.
        TimeLog::factory()->for($user, 'user')->create(['hours_worked' => 140, 'work_date' => now()]);
        TimeLog::factory()->for($user, 'user')->create(['hours_worked' => 20, 'work_date' => now()->subMonth()]);

        // --- Create Leave Applications for Jane ---
        // 5 approved days this year. Other leave should be ignored by yearly calculations.
        LeaveApplication::factory()->for($user)->create(['status' => 'approved', 'leave_days' => 5, 'start_date' => now()]);
        LeaveApplication::factory()->for($user)->create(['status' => 'pending', 'leave_days' => 2, 'start_date' => now()]); // Should not be counted as "used"
        LeaveApplication::factory()->for($user)->create(['status' => 'approved', 'leave_days' => 3, 'start_date' => now()->subYear()]); // Should be ignored by current year stats

        // =================================================================
        // 2. ACT & 3. ASSERT: Verify every calculation is correct.
        // =================================================================

        // --- Assert Basic Attributes ---
        $this->assertEquals(Storage::url('avatars/jane.jpg'), $user->avatar_url);

        // --- Assert Hierarchy & Permissions ---
        $this->assertTrue($user->isManagerOf($subordinate));
        $this->assertFalse($user->isManagerOf($manager)); // Can't be your own manager's manager
        $this->assertTrue($adminUser->canApproveLeaveFor($user)); // Admin has power
        $this->assertTrue($manager->canApproveLeaveFor($user));   // Direct manager can approve
        $this->assertFalse($otherUser->canApproveLeaveFor($user)); // Unrelated user cannot

        // --- Assert Task & Time Calculations ---
        $this->assertEquals(75.0, $user->getTaskCompletionRate(), 'Task completion rate should be 75%.');
        $this->assertEquals(140, $user->getCurrentMonthHours(), 'Should only sum hours from the current month.');

        // --- Assert Leave Calculations ---
        $this->assertEquals(5, $user->getUsedLeaveDays(), 'Used leave days should only count approved applications from the current year.');
        $this->assertEquals(20, $user->getRemainingLeaveBalance(), 'Remaining balance should be total (25) minus used this year (5).');

        // --- Assert the Final Performance Score ---
        // Let's calculate the expected score manually:
        // Task Score = 75
        // Time Score = min(100, (140 / 160) * 100) = min(100, 87.5) = 87.5
        // Leave Score = max(0, 100 - (5 used / 25 total) * 100) = max(0, 100 - 20) = 80
        //
        // Final Score = round( (75 + 87.5 + 80) / 3 )
        //             = round( 242.5 / 3 )
        //             = round( 80.833... )
        //             = 81
        $this->assertEquals(81, $user->getPerformanceScore(), 'The final performance score calculation is incorrect.');
    }
}
