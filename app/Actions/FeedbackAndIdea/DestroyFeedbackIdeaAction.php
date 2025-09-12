<?php

namespace App\Actions\FeedbackAndIdea;

use App\Models\FeedbackIdea;

class DestroyFeedbackIdeaAction
{
    public function execute(int $id): FeedbackIdea
    {
        $item = FeedbackIdea::findOrFail($id);
        $item->delete();

        return $item;
    }
}
