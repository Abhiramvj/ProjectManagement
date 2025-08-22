<?php

namespace Tests\Feature;

use App\Actions\TimeLog\GetTimeLogs;
use App\Actions\TimeLog\StoreTimeLogs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class TimeLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate a user for middleware
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_index_returns_inertia_with_time_logs()
    {
        $mockGetTimeLogs = Mockery::mock(GetTimeLogs::class);
        $mockGetTimeLogs->shouldReceive('handle')
            ->once()
            ->andReturn([
                'timeLogs' => [
                    ['id' => 1, 'user_id' => $this->user->id, 'hours' => 5, 'date' => '2025-08-21'],
                    // Add more dummy records if needed
                ],
            ]);

        $this->app->instance(GetTimeLogs::class, $mockGetTimeLogs);

        $response = $this->get(route('hours.index'));

        $response->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Hours/Index')
                ->has('timeLogs', 1)
                ->etc()
            );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_store_validates_and_redirects()
    {
        $project = \App\Models\Project::factory()->create();

        $mockStoreTimeLogs = Mockery::mock(StoreTimeLogs::class);
        $mockStoreTimeLogs->shouldReceive('handle')
            ->once()
            ->withArgs(function ($data) use ($project) {
                return isset($data['project_id'], $data['work_date'], $data['hours_worked']) &&
                    $data['project_id'] === $project->id &&
                    $data['work_date'] <= now()->toDateString() &&
                    is_numeric($data['hours_worked']);
            });

        $this->app->instance(StoreTimeLogs::class, $mockStoreTimeLogs);

        $response = $this->post(route('hours.store'), [
            'project_id' => $project->id,
            'work_date' => now()->toDateString(),  // today or before
            'hours_worked' => 5,
        ]);

        $response->assertRedirect(route('hours.index'));
        $response->assertSessionHas('success', 'Working hours logged successfully.');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
