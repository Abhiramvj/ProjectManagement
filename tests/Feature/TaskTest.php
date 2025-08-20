<?php

namespace Tests\Feature;

use App\Actions\Task\StoreTask;
use App\Actions\Task\UpdateTaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->project = Project::factory()->create([
            'project_manager_id' => $this->user->id,
        ]);
    }

    public function test_store_validates_and_stores_task()
    {
        $mockStoreTask = Mockery::mock(StoreTask::class);
        $mockStoreTask->shouldReceive('handle')
            ->once()
            ->withArgs(function ($project, $data) {
                return $data['name'] === 'Test Task' && isset($data['assigned_to_id']);
            });

        $this->app->instance(StoreTask::class, $mockStoreTask);

        $response = $this->post(route('tasks.store', $this->project), [
            'name' => 'Test Task',
            'assigned_to_id' => $this->user->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Task created successfully.');
    }

    public function test_update_status_validates_and_updates_task_status()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $mockUpdateTaskStatus = Mockery::mock(UpdateTaskStatus::class);

        $mockUpdateTaskStatus->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($taskArg) => $taskArg->id === $task->id), 'in_progress');

        $this->app->instance(UpdateTaskStatus::class, $mockUpdateTaskStatus);

        $response = $this->patch(route('tasks.updateStatus', $task), [
            'status' => 'in_progress',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Task status updated.');
    }

    public function test_update_validates_and_updates_task()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to_id' => $this->user->id,
        ]);

        $updateData = [
            'name' => 'Updated Task Name',
            'assigned_to_id' => $this->user->id,
            'status' => 'completed',
            'due_date' => now()->addWeek()->toDateString(),
        ];

        $response = $this->patch(route('tasks.update', $task), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Task updated successfully.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Updated Task Name',
            'status' => 'completed',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
