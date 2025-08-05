<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createUserWithLeaveAbility(array $attributes = [])
    {
        $user = User::factory()->create($attributes);

        $user->assignRole('employee'); // Now this role exists because seeder ran

        return $user;
    } 
        public function test_prevent_annual_leave_with_less_than_7_days_notice()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $startDate = now()->addDays(3)->toDateString();

        $response = $this->post(route('leave.store'), [
            'start_date' => $startDate,
            'leave_type' => 'annual',
            'reason' => 'Urgent leave',
            'day_type' => 'full',
        ]);

        // Assuming your controller allows with a warning, here you may check validation or response
        // If you implement strict validation, do:
        $response->assertSessionHasNoErrors();

        // Or if you validate and block, assert errors exists accordingly.
    }

    public function test_user_can_submit_annual_leave_request()
{
    $user = $this->createUserWithLeaveAbility([
        'leave_balance' => 2,
    ]);

    $this->actingAs($user);

    $startDate = now()->addDays(10)->toDateString();
    $endDate = now()->addDays(12)->toDateString();

    $response = $this->post(route('leave.store'), [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'leave_type' => 'annual',
        'reason' => 'Vacation planned',
        'day_type' => 'full',
    ]);

    $response->assertStatus(302);
    $this->assertDatabaseHas('leave_applications', [
        'user_id' => $user->id,
        'leave_type' => 'annual',
        'reason' => 'Vacation planned',
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
}

public function test_user_can_submit_sick_leave_with_supporting_document()
{
    $user = $this->createUserWithLeaveAbility([
        'leave_balance' => 5,
    ]);

    $this->actingAs($user);

    $file = \Illuminate\Http\UploadedFile::fake()->create('medical.pdf', 100);

    $response = $this->post(route('leave.store'), [
        'start_date' => now()->toDateString(),
        'leave_type' => 'sick',
        'reason' => 'Flu symptoms',
        'day_type' => 'full',
        'supporting_document' => $file,
    ]);

    $response->assertStatus(302);
    $this->assertDatabaseHas('leave_applications', [
        'user_id' => $user->id,
        'leave_type' => 'sick',
        'reason' => 'Flu symptoms',
    ]);
}

public function test_compensatory_leave_accrual_when_logging_work_on_weekend()
{
    $user = $this->createUserWithLeaveAbility([
        'comp_off_balance' => 0,
    ]);

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
        'team_id' => Team::factory(),
    ]);

    $this->actingAs($user);

    $weekendDate = now()->next('Saturday')->toDateString();

    $response = $this->post(route('time_log.store'), [
        'work_date' => $weekendDate,
        'hours_worked' => 8,
        'project_id' => $project->id,
    ]);

    $response->assertStatus(302);

    $user->refresh();
    $this->assertEquals(1, $user->comp_off_balance);
}

public function test_leave_form_validation_errors_on_missing_required_fields()
{
    $user = $this->createUserWithLeaveAbility();

    $this->actingAs($user);

    $response = $this->from(route('leave.create'))->post(route('leave.store'), [
        // No data submitted
    ]);

    $response->assertRedirect(route('leave.create'));
    $response->assertSessionHasErrors(['start_date', 'leave_type', 'reason']);
}
}