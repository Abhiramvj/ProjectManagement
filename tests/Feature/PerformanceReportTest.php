<?php

namespace Tests\Feature;

use App\Actions\Performance\ShowPerformance;
use App\Http\Controllers\PerformanceReportController;
use App\Models\User;
use App\Services\OllamaAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user with permission
        $this->authUser = User::factory()->create();
        $permission = Permission::firstOrCreate(['name' => 'manage employees']);
        $this->authUser->givePermissionTo($permission);

        $this->actingAs($this->authUser);
    }

    public function test_show_returns_inertia_response_with_expected_props()
{
    $targetUser = User::factory()->create();

    $mockAction = Mockery::mock(ShowPerformance::class);
    $mockAction->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(User::class)) // <-- type matcher
        ->andReturn([
            'score' => 95,
            'details' => 'Excellent performance',
        ]);

    $this->app->instance(ShowPerformance::class, $mockAction);

    $response = $this->actingAs($this->authUser)
        ->get(route('performance.show', $targetUser));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Performance/Show')
            ->where('score', 95)
            ->where('details', 'Excellent performance')
        );
}


    public function test_generate_summary_returns_json_with_summary()
    {
        $targetUser = User::factory()->create();

        $ollamaService = Mockery::mock(OllamaAIService::class);
        $ollamaService->shouldReceive('generateText')
            ->once()
            ->andReturn('Sample performance summary text.');

        $requestData = [
            'taskStats' => ['completion_rate' => 75.5, 'completed' => 15, 'total' => 20],
            'timeStats' => ['current_month' => 120.3],
            'leaveStats' => ['current_year' => 5, 'balance' => 20],
            'performanceScore' => 78.5,
        ];

        $request = Request::create('/generate-summary', 'POST', $requestData);

        $controller = new PerformanceReportController;

        $response = $controller->generateSummary($request, $targetUser, $ollamaService);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('Sample performance summary text.', $responseData['summary']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_generate_summary_returns_error_when_ollama_fails()
    {
        $targetUser = User::factory()->create();

        $ollamaService = Mockery::mock(OllamaAIService::class);
        $ollamaService->shouldReceive('generateText')->once()->andReturn(null);

        Log::shouldReceive('error')->once();

        $requestData = [
            'taskStats' => ['completion_rate' => 75.5, 'completed' => 15, 'total' => 20],
            'timeStats' => ['current_month' => 120.3],
            'leaveStats' => ['current_year' => 5, 'balance' => 20],
            'performanceScore' => 78.5,
        ];

        $request = Request::create('/generate-summary', 'POST', $requestData);
        $controller = new PerformanceReportController;

        $response = $controller->generateSummary($request, $targetUser, $ollamaService);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('The AI summary could not be generated at this time.', $responseData['error']);
    }

    public function test_generate_summary_validation_fails()
    {
        $targetUser = User::factory()->create();
        $ollamaService = Mockery::mock(OllamaAIService::class);

        $request = Request::create('/generate-summary', 'POST', []); // Empty data

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $controller = new PerformanceReportController;
        $controller->generateSummary($request, $targetUser, $ollamaService);
    }

    public function test_generate_my_summary_returns_json_with_summary()
    {
        $ollamaService = Mockery::mock(OllamaAIService::class);
        $ollamaService->shouldReceive('generateText')
            ->once()
            ->andReturn('Sample my performance summary text.');

        $requestData = [
            'taskStats' => ['completion_rate' => 80.0],
            'timeStats' => ['current_month' => 100.5],
            'leaveStats' => ['current_year' => 3, 'balance' => 22],
            'performanceScore' => 82.0,
        ];

        $request = Request::create('/generate-my-summary', 'POST', $requestData);
        $controller = new PerformanceReportController;

        $response = $controller->generateMySummary($request, $ollamaService);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('Sample my performance summary text.', $responseData['summary']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_generate_my_summary_returns_error_when_ollama_fails()
    {
        $ollamaService = Mockery::mock(OllamaAIService::class);
        $ollamaService->shouldReceive('generateText')->once()->andReturn(null);

        Log::shouldReceive('error')->once();

        $requestData = [
            'taskStats' => ['completion_rate' => 80.0],
            'timeStats' => ['current_month' => 100.5],
            'leaveStats' => ['current_year' => 3, 'balance' => 22],
            'performanceScore' => 82.0,
        ];

        $request = Request::create('/generate-my-summary', 'POST', $requestData);
        $controller = new PerformanceReportController;

        $response = $controller->generateMySummary($request, $ollamaService);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('The AI summary could not be generated at this time.', $responseData['error']);
    }

    public function test_generate_my_summary_validation_fails()
    {
        $ollamaService = Mockery::mock(OllamaAIService::class);
        $request = Request::create('/generate-my-summary', 'POST', []); // Empty data

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $controller = new PerformanceReportController;
        $controller->generateMySummary($request, $ollamaService);
    }
}
