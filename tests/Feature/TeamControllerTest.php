<?php

namespace Tests\Feature;

use App\Actions\Team\DeleteTeam;
use App\Actions\Team\GetTeams;
use App\Actions\Team\StoreTeams;
use App\Actions\Team\UpdateTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Assign 'admin' or appropriate role to pass authorization middleware
        Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole('admin');

        $this->actingAs($this->user);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_index_returns_inertia_with_teams()
    {
        $mockGetTeams = Mockery::mock(GetTeams::class);
        $mockGetTeams->shouldReceive('handle')
            ->once()
            ->andReturn([
                'teams' => Team::factory()->count(3)->make()->toArray(),
            ]);

        $this->app->instance(GetTeams::class, $mockGetTeams);

        $response = $this->get(route('teams.index'));

        $response->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Teams/Index')
                ->has('teams', 3)
                ->etc()
            );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_store_handles_validated_data_and_redirects()
    {
        $teamLead = User::factory()->create();

        $mockStoreTeams = Mockery::mock(StoreTeams::class);
        $mockStoreTeams->shouldReceive('handle')->once()->with([
            'name' => 'New Team',
            'team_lead_id' => $teamLead->id, // Only validated fields
        ]);

        $this->app->instance(StoreTeams::class, $mockStoreTeams);

        $response = $this->post(route('teams.store'), [
            'name' => 'New Team',
            'team_lead_id' => $teamLead->id, // Only send validated fields
        ]);

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success', 'Team created successfully.');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_update_handles_validated_data_and_redirects()
    {
        $team = Team::factory()->create();
        $teamLead = User::factory()->create();

        $mockUpdateTeam = Mockery::mock(UpdateTeam::class);
        $mockUpdateTeam->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::type(Team::class),
                Mockery::on(function ($data) use ($teamLead) {
                    return $data['name'] === 'Updated Team'
                        && $data['team_lead_id'] === $teamLead->id;
                })
            );

        $this->app->instance(UpdateTeam::class, $mockUpdateTeam);

        $response = $this->put(route('teams.update', $team), [
            'name' => 'Updated Team',
            'description' => 'Updated description',
            'team_lead_id' => $teamLead->id, // Send in request
        ]);

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success', 'Team updated successfully.');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_destroy_handles_delete_and_redirects()
    {
        $team = Team::factory()->create();

        $mockDeleteTeam = Mockery::mock(DeleteTeam::class);
        // Relax argument matching for the team to any instance of Team
        $mockDeleteTeam->shouldReceive('handle')
            ->once()
            ->with(Mockery::type(Team::class));

        $this->app->instance(DeleteTeam::class, $mockDeleteTeam);

        $response = $this->delete(route('teams.destroy', $team));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success', 'Team deleted successfully.');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
