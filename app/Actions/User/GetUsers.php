<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Http\Request;

class GetUsers
{
    protected $createUsers;

    public function __construct(CreateUsers $createUsers)
    {
        $this->createUsers = $createUsers;
    }

    public function handle(Request $request)
    {
        // 1. Get ALL data for the creation modal (roles, teams, managers, workModes).
        $formProps = $this->createUsers->handle();

        // 2. Get the paginated list of users for the table.
        $users = User::with('roles:id,name')
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // 3. Merge the two sets of data together to be sent to the page.
        return array_merge([
            'users' => $users,
            'filters' => $request->only(['search']),
        ], $formProps); // $formProps already contains 'workModes'
    }
}
