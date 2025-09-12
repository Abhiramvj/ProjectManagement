<?php

namespace App\Actions\FeedbackAndIdea;

use App\Models\FeedbackIdea;

class UpdateFeedbackIdeaAction
{
    public function execute(int $id, string $description): FeedbackIdea
    {
        $item = FeedbackIdea::findOrFail($id);
        $item->description = $description;
        $item->save();

        return $item;
    }
}
