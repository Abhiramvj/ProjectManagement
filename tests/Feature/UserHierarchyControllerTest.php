<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UserHierarchyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and authenticate for permission checks
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Setup related data for users and teams
        $team = Team::factory()->create();

        // Create users with parent-child relationships and assigned teams
        $manager = User::factory()->create([
            'parent_id' => null,
            'designation' => 'Manager',
        ]);
        $manager->teams()->attach($team);

        $employee = User::factory()->create([
            'parent_id' => $manager->id,
            'designation' => 'Employee',
        ]);
        $employee->teams()->attach($team);

        // Optionally create tasks count and completed tasks count if you track tasks
        // Use Model Factories or manually set counts on models if needed
    }

    public function testIndexReturnsHierarchyData()
    {
        $response = $this->get(route('company.hierarchy')); // Adjust route name as needed

        $response->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Hierarchy/CompanyHierarchy')
                ->has('reportingNodes')
                ->has('designationBasedNodes')
            );
    }
}
