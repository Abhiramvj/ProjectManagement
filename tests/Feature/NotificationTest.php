<?php

namespace Tests\Feature;

use App\Models\LeaveApplication;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\LeaveRequestApproved;
use App\Notifications\LeaveRequestRejected;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $authUser;

    protected LeaveApplication $leaveApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::firstOrCreate(['name' => 'employee']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'hr']);
        Role::firstOrCreate(['name' => 'team-lead']);

        // Create authenticated user (approver)
        $this->authUser = User::factory()->create(['name' => 'ApproverUser']);
        $this->actingAs($this->authUser);

        // Create user who applied for leave
        $leaveUser = User::factory()->create(['name' => 'LeaveUser']);

        // Create LeaveApplication for testing notifications
        $this->leaveApplication = LeaveApplication::factory()->create([
            'user_id' => $leaveUser->id,
            'start_date' => '2025-09-01',
            'end_date' => '2025-09-10',
        ]);
    }

    // --- NotificationController Tests ---

    public function test_index_returns_notifications()
    {
        // Add some notifications
        $this->authUser->notify(new LeaveRequestApproved($this->leaveApplication));
        $this->authUser->notify(new LeaveRequestRejected($this->leaveApplication));

        $response = $this->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Notifications/Index')->has('notifications')
        );
    }

    public function test_mark_as_read_marks_notification(): void
    {
        // Ensure authUser has a role so scopeQueryByUserRole passes
        $this->authUser->assignRole('employee');

        // Create a notification
        $notification = Notification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'notifiable_id' => $this->authUser->id,
            'notifiable_type' => User::class,
            'type' => LeaveRequestApproved::class,
            'data' => ['leave_id' => $this->leaveApplication->id],
            'read_at' => null,
        ]);

        $response = $this->postJson(route('notifications.read', $notification->id));

        $response->assertOk()->assertJson(['success' => true]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_all_as_read_marks_all_notifications(): void
    {
        $this->authUser->assignRole('employee');

        $notifications = collect();
        for ($i = 0; $i < 3; $i++) {
            $notifications->push(Notification::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'notifiable_id' => $this->authUser->id,
                'notifiable_type' => User::class,
                'type' => LeaveRequestApproved::class,
                'data' => ['leave_id' => $this->leaveApplication->id],
                'read_at' => null,
            ]));
        }

        $response = $this->postJson(route('notifications.mark-all-read'));
        $response->assertOk()->assertJson(['success' => true]);

        foreach ($notifications as $notification) {
            $notification->refresh();
            $this->assertNotNull($notification->read_at);
        }
    }

    public function test_get_unread_count_returns_correct_count()
    {
        $this->authUser->notify(new LeaveRequestApproved($this->leaveApplication));

        $response = $this->getJson(route('notifications.unread-count'));

        $response->assertOk()->assertJson([
            'count' => 1,
        ]);
    }

    public function test_get_recent_returns_notifications()
    {
        $this->authUser->notify(new LeaveRequestApproved($this->leaveApplication));

        $response = $this->getJson(route('notifications.recent'));

        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
    }

    // --- Notification Classes Tests ---

    public function test_leave_request_approved_to_array()
    {
        $notification = new LeaveRequestApproved($this->leaveApplication);

        $array = $notification->toArray($this->authUser);

        $this->assertEquals('Leave Request Approved', $array['title']);
        $this->assertStringContainsString('2025-09-01', $array['message']);
        $this->assertStringContainsString('2025-09-10', $array['message']);
        $this->assertEquals('leave_approved', $array['type']);
        $this->assertEquals($this->leaveApplication->id, $array['leave_id']);
        $this->assertEquals('ApproverUser', $array['approved_by']);
        $this->assertStringContainsString(route('leave.index'), $array['url']);
    }

    public function test_leave_request_rejected_to_array()
    {
        $notification = new LeaveRequestRejected($this->leaveApplication);

        $array = $notification->toArray($this->authUser);

        $this->assertEquals('Leave Request Rejected', $array['title']);
        $this->assertStringContainsString('2025-09-01', $array['message']);
        $this->assertStringContainsString('2025-09-10', $array['message']);
        $this->assertEquals('leave_rejected', $array['type']);
        $this->assertEquals($this->leaveApplication->id, $array['leave_id']);
        $this->assertEquals('ApproverUser', $array['rejected_by']);
        $this->assertStringContainsString(route('leave.index'), $array['url']);
    }

    public function test_leave_request_submitted_to_array()
    {
        $notification = new LeaveRequestSubmitted($this->leaveApplication);

        $array = $notification->toArray($this->authUser);

        $this->assertEquals('New Leave Request', $array['title']);
        $this->assertStringContainsString($this->leaveApplication->user->name, $array['message']);
        $this->assertEquals('leave_request', $array['type']);
        $this->assertEquals($this->leaveApplication->id, $array['leave_id']);
        $this->assertEquals($this->leaveApplication->user->name, $array['user_name']);
        $this->assertStringContainsString(route('leave.index'), $array['url']);
    }
}
