<?php

namespace App\Http\Controllers;

use App\Actions\User\DeleteUser;
use App\Actions\User\GetUsers;
use App\Actions\User\StoreUsers;
use App\Actions\User\UpdateUser;
use App\Actions\User\ImportUsers; // <-- CORRECTED: Points to the Action class
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // <-- 1. IMPORT THE LOG FACADE
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
            // If any error occurs, log it and re-throw to see the error page
            Log::error('Failed to load user index page: ' . $e->getMessage());
            Log::error($e); // This logs the full exception details

            // During development, re-throwing is useful to see the error screen.
            // In production, you might want to return an error view.
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
            Log::error('Failed to store new user: ' . $e->getMessage());
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
            Log::error("Failed to update user (ID: {$user->id}): " . $e->getMessage());
            Log::error($e);

            return Redirect::back()->with('error', 'An unexpected error occurred while updating the user.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user, DeleteUser $deleteUser)
    {
        try {
            $deleteUser->handle($user);
            return Redirect::route('users.index')->with('success', 'Employee deleted successfully.');

        } catch (\Throwable $e) {
            Log::error("Failed to delete user (ID: {$user->id}): " . $e->getMessage());
            Log::error($e);

            return Redirect::back()->with('error', 'An unexpected error occurred while deleting the user.');
        }
    }

    /**
     * Handle the user import process.
     */
     public function import(Request $request, ImportUsers $importUsers)
     {
        try {
            return $importUsers->handle($request);

        } catch (\Throwable $e) {
            // This will catch any errors NOT handled by the ImportUsers action itself,
            // such as a file system error or a database connection issue.
            Log::error('A critical error occurred during the user import process: ' . $e->getMessage());
            Log::error($e);

            // The 'ValidationException' is already handled in your ImportUsers action.
            // This redirect is for all other unexpected errors.
            return Redirect::back()->with('error', 'A critical error occurred. Please check the log file.');
        }
     }
}
