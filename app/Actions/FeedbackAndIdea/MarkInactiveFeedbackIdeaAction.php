<?php

namespace App\Actions\FeedbackAndIdea;

use App\Models\FeedbackIdea;
use Illuminate\Support\Facades\Auth;

class MarkInactiveFeedbackIdeaAction
{
    public function execute(int $id): FeedbackIdea
    {
        $item = FeedbackIdea::findOrFail($id);

        if (! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('hr')) {
            abort(403);
        }

        $item->update(['is_active' => false]);

        return $item;
    }
}
