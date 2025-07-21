<?php

namespace App\Http\Controllers;

use App\Actions\User\CreateUsers;
use App\Actions\User\GetUsers;
use App\Actions\User\StoreUsers;
use App\Http\Requests\User\StoreUserRequest;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(GetUsers $getUsers)
    {
        return Inertia::render('Users/Index', $getUsers->handle());
    }

    // public function create(CreateUsers $createUsers)
    // {
    //     $data = $createUsers->handle();

    //     return Inertia::render('Users/Create', $data);
    // }

    public function store(StoreUserRequest $request, StoreUsers $storeUsers)
    {
        $storeUsers->handle($request->validated());

        return redirect()->route('users.index')->with('success', 'Employee added successfully.');
    }
}
