<?php

namespace App\Actions;

use App\Models\User;
use App\Services\OllamaAIService;
use Illuminate\Support\Facades\Log;

class GenerateMySummaryAction
{
    protected OllamaAIService $ollamaAiService;

    public function __construct(OllamaAIService $ollamaAiService)
    {
        $this->ollamaAiService = $ollamaAiService;
    }

    public function handle(array $stats, User $user): ?string
    {
        $name = $user->name;

        $prompt = "
            As an HR manager, write a concise, professional, and encouraging performance review summary for an employee named {$name}.
            The tone should be supportive, highlighting strengths and gently suggesting areas for growth.
            Do not use markdown formatting. Write in plain text paragraphs.

            Use the following data to inform your summary:
            - Overall Performance Score: {$stats['performanceScore']}%
            - Task Completion Rate: {$stats['taskStats']['completion_rate']}%
            - Total Hours Logged this Month: {$stats['timeStats']['current_month']} hours.
            - Leave Days Taken This Year: {$stats['leaveStats']['current_year']} out of a total allowance of {$stats['leaveStats']['balance']} days.

            Based on these metrics, provide a 1-2 paragraph summary of their performance.
        ";

        try {
            return $this->ollamaAiService->generateText($prompt, 'llama3');
        } catch (\Exception $e) {
            Log::error("Failed to generate 'my summary' for user {$user->id}: {$e->getMessage()}");

            return null;
        }
    }
}
