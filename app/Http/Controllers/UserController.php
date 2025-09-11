<?php

namespace App\Http\Controllers;

use App\Actions\User\DeleteUser;
use App\Actions\User\GetUsers;
use App\Actions\User\StoreUsers;
use App\Actions\User\UpdateUser;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Jobs\ProcessUserImport;
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
    public function index(Request $request, GetUsers $getUsers)
    {
        try {
            // The main logic of the method
            return Inertia::render('Users/Index', $getUsers->handle($request));

        } catch (\Throwable $e) {
            Log::error('Failed to load user index page: '.$e->getMessage());
            Log::error($e);
            throw $e;
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request, StoreUsers $storeUsers)
    {
        try {
            $storeUsers->handle($request->validated());

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
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser)
    {
        try {
            $updateUser->handle($user, $request->validated());

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
    public function destroy(User $user, DeleteUser $deleteUser)
    {
        $deleteUser->handle($user);

        return Redirect::route('users.index')->with('success', 'Employee deleted successfully.');
    }

    public function search(Request $request)
    {
        $query = $request->input('query', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    public function import(Request $request)
    {
        // 1. Validate the request to ensure a file was uploaded
        try {
            $request->validate([
                'file' => 'required|mimes:csv,txt,xlsx',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::back()->withErrors($e->errors());
        }

        try {
            // 2. Store the file in a temporary location (e.g., storage/app/imports)
            $path = $request->file('file')->store('imports');

            // 3. Dispatch the job to the queue. This is an instantaneous action.
            ProcessUserImport::dispatch($path);

            // 4. Redirect back to the user with a success message. No waiting!
            return Redirect::back()->with('success', 'File uploaded! The users are being imported in the background.');

        } catch (\Throwable $e) {
            // This will catch rare errors like file system permissions or queue connection issues.
            Log::critical('Failed to store file or dispatch import job: '.$e->getMessage());
            Log::critical($e);

            return Redirect::back()->with('error', 'Could not start the import process. Please contact support.');
        }
    }
}
