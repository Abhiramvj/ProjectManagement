<?php

namespace App\Http\Controllers;

use App\Actions\User\DeleteUserAction;
use App\Actions\User\GetUsersAction;
use App\Actions\User\ImportUsersAction;
use App\Actions\User\SearchUsersAction;
use App\Actions\User\StoreUsersAction;
use App\Actions\User\UpdateUserAction;
use App\Http\Requests\User\ImportUsersRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, GetUsersAction $getUsers)
    {
        try {
            // The main logic of the method
            return Inertia::render('Users/Index', $getUsers->execute($request));

        } catch (\Throwable $e) {
            Log::error('Failed to load user index page: '.$e->getMessage());
            Log::error($e);
            throw $e;
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request, StoreUsersAction $storeUsers)
    {
        try {
            $storeUsers->execute($request->validated());

            return redirect()->route('users.index')->with('success', 'Employee added successfully.');

        } catch (\Throwable $e) {
            Log::error('Failed to store new user: '.$e->getMessage());
            Log::error($e);

            return Redirect::back()->with('error', 'An unexpected error occurred while adding the user.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $updateUser)
    {
        try {
            $updateUser->execute($user, $request->validated());

            return Redirect::route('users.index')->with('success', 'Employee details updated successfully.');

        } catch (\Throwable $e) {
            Log::error("Failed to update user (ID: {$user->id}): ".$e->getMessage());
            Log::error($e);

            return Redirect::back()->with('error', 'An unexpected error occurred while updating the user.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user, DeleteUserAction $deleteUser)
    {
        $deleteUser->execute($user);

        return Redirect::route('users.index')->with('success', 'Employee deleted successfully.');
    }

    public function search(Request $request, SearchUsersAction $searchUsersAction)
    {
        $query = $request->input('query', '');

        $users = $searchUsersAction->execute($query);

        return response()->json($users);
    }

    public function import(ImportUsersRequest $request, ImportUsersAction $importUsersAction)
    {
        return $importUsersAction->execute($request->file('file'));
    }
}
