<?php

namespace App\Actions\FeedbackAndIdea;

use App\Models\FeedbackIdea;

class ToggleFeedbackIdeaAction
{
    public function execute(int $id): FeedbackIdea
    {
        $item = FeedbackIdea::findOrFail($id);
        $item->is_active = ! $item->is_active;
        $item->save();

        return $item;
    }
}
