<?php

namespace Tests\Feature;

use App\Actions\Project\CreateProject;
use App\Actions\Project\ShowProject;
use App\Actions\Project\StoreProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = User::factory()->create();

        // Assign admin role or any role that allows viewing projects
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->authUser->assignRole($role);

        $this->actingAs($this->authUser);
    }

    public function test_index_returns_inertia_with_projects_and_creation_data()
    {
        // Create 3 projects with authUser as project_manager, so they appear in scope
        Project::factory()->count(3)->create(['project_manager_id' => $this->authUser->id]);

        $mockCreateProject = Mockery::mock(CreateProject::class);
        $mockCreateProject->shouldReceive('handle')->once()->andReturn([
            'teams' => ['team1', 'team2'],
            'projectManagers' => ['manager1', 'manager2'],
        ]);
        $this->app->instance(CreateProject::class, $mockCreateProject);

        $response = $this->get(route('projects.index'));

        $response->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Projects/Index')
                ->has('projects', 3)         // asserts 3 projects exist
                ->where('teams', ['team1', 'team2'])
                ->where('projectManagers', ['manager1', 'manager2'])
                ->etc()
            );
    }

    public function test_store_handles_request_and_redirects()
    {
        $mockStoreProject = Mockery::mock(StoreProject::class);
        $mockStoreProject->shouldReceive('handle')->once();

        $mockRequest = $this->mock(\App\Http\Requests\Project\StoreProjectRequest::class, function ($mock) {
            $mock->shouldReceive('validated')->andReturn([
                'name' => 'Test Project',
                'team_id' => 1,
                'project_manager_id' => $this->authUser->id,
                'status' => 'active',
                'priority' => 'high',
                'total_hours_required' => 50,
                'end_date' => null,
            ]);
        });

        $controller = app()->make(\App\Http\Controllers\ProjectController::class);

        $response = $controller->store($mockRequest, $mockStoreProject);

        $this->assertEquals(route('projects.index'), $response->getTargetUrl());
        $this->assertTrue(session()->has('success'));
    }

    public function test_show_returns_inertia_with_project_data()
    {
        $project = Project::factory()->create(['project_manager_id' => $this->authUser->id]);

        $mockShowProject = Mockery::mock(ShowProject::class);
        $mockShowProject->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $project->id))
            ->andReturn([
                'project' => $project,
                'assignableUsers' => ['user1', 'user2'],
            ]);
        $this->app->instance(ShowProject::class, $mockShowProject);

        $response = $this->get(route('projects.show', $project));

        $response->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Projects/Show')
                ->where('project.id', $project->id)
                ->where('assignableUsers', ['user1', 'user2'])
                ->etc()
            );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
