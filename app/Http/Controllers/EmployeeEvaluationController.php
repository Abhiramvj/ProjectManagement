<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;

class EmployeeEvaluationController extends Controller
{
    protected $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    public function evaluate(Request $request)
    {
        $request->validate(['prompt' => 'required|string']);

        $evaluation = $this->openAI->generateEvaluation($request->input('prompt'));

        return response()->json(['summary' => $evaluation]);
    }
}
