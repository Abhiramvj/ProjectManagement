<?php

namespace App\Actions\FeedbackAndIdea;

use App\Models\FeedbackIdea;
use Illuminate\Support\Facades\Auth;

class StoreFeedbackIdeaAction
{
    public function execute(string $type, string $description): FeedbackIdea
    {
        return FeedbackIdea::create([
            'user_id' => Auth::id(),
            'type' => $type,
            'description' => $description,
        ]);
    }
}
