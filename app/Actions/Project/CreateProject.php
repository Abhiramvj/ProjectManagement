<?php

namespace App\Actions\Project;

use App\Models\Team;

class CreateProject
{
    public function handle()
    {
        return [
            'teams' => Team::with('teamLead:id,name')->get(),
        ];
    }
}
