<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiAIService;

class GeminiController extends Controller
{
    protected GeminiAIService $gemini;

    public function __construct(GeminiAIService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Receives prompt and returns AI-generated employee evaluation
     */
    public function generate(Request $request)
    {
        $request->validate(['prompt' => 'required|string']);

        $summary = $this->gemini->generateContent($request->input('prompt'));

        return response()->json(['summary' => $summary]);
    }
}
