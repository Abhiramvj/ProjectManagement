<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiAIService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Calls Google Gemini API to generate content based on the prompt
     * 
     * @param string $prompt
     * @return string
     */
    public function generateContent(string $prompt): string
    {
        $response = Http::post($this->apiUrl . '?key=' . $this->apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->failed()) {
            \Log::error('Gemini API Error', ['body' => $response->body()]);
            return 'Error: Unable to generate AI content.';
        }

        return $response->json('candidates.0.content.parts.0.text') ?? 'Error: No AI response.';
    }

}
