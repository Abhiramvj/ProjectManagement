<?php

namespace App\Services;

// We no longer need the Ollama Facade, as we are making a direct connection.
use Exception;
use Illuminate\Support\Facades\Log;

class OllamaAIService
{
    private string $ollamaApiUrl;

    public function __construct()
    {
        // Build the base URL from your config file.
        $url = config('ollama.url', 'http://127.0.0.1');
        $port = config('ollama.port', 11434);
        $this->ollamaApiUrl = rtrim($url, '/').':'.$port;
    }

    /**
     * Generate text content using a prompt.
     * This method now uses a direct cURL request that we have proven works.
     */
    public function generateText(string $prompt, string $model = 'llama3'): ?string
    {
        try {
            $data = [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false, // We want the full response at once
            ];

            $responseJson = $this->sendRequest('/api/generate', $data);

            // The API response is JSON, so we decode it.
            $responseObject = json_decode($responseJson);

            // Check if the response and the text exist, then return it.
            if (isset($responseObject->response)) {
                return $responseObject->response;
            }

            // If the response key is missing, something is wrong.
            throw new Exception('The "response" key was not found in the Ollama API response.');
        } catch (Exception $e) {
            Log::error('Ollama API Error: '.$e->getMessage(), [
                'model' => $model,
                'prompt' => $prompt,
            ]);

            return null;
        }
    }

    // NOTE: I have left the other methods (chat, image) as placeholders.
    // They would need to be rewritten using cURL as well if you need them.
    // This `generateText` method is all you need to fix your current problem.

    public function startNewChat(): array
    {
        return [];
    }

    public function sendMessageInChat(array $history, string $message, string $model = 'llama3'): ?array
    {
        // This would need to be rewritten using the cURL helper method.
        // For now, it will return null.
        Log::warning('OllamaAIService::sendMessageInChat is not implemented for direct cURL.');

        return null;
    }

    public function generateContentWithImage(string $textPrompt, string $imagePath, string $model = 'llama3'): ?string
    {
        // This would need to be rewritten using the cURL helper method.
        // For now, it will return null.
        Log::warning('OllamaAIService::generateContentWithImage is not implemented for direct cURL.');

        return null;
    }

    /**
     * A private helper method to send requests to the Ollama API using cURL.
     *
     * @param  string  $endpoint  e.g., '/api/generate'
     * @param  array  $data  The data to be sent as JSON.
     * @return string|false The raw JSON response string or false on failure.
     */
    private function sendRequest(string $endpoint, array $data): string|false
    {
        $jsonData = json_encode($data);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->ollamaApiUrl.$endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, config('ollama.request_timeout', 300));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            // Throw an exception so our main method can catch and log it.
            throw new Exception('cURL Error: '.$error);
        }

        curl_close($ch);

        return $response;
    }
}
