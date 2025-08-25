<?php

namespace App\Http\Controllers;

use App\Actions\User\DeleteUser;
use App\Actions\User\GetUsers;
use App\Actions\User\StoreUsers;
use App\Actions\User\UpdateUser;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * This method now handles displaying the user list AND providing
     * all necessary data for the create/edit modals.
     */
    public function index(Request $request, GetUsers $getUsers) // <-- Inject the Request
    {
        // Pass the incoming request into the action's handle method
        return Inertia::render('Users/Index', $getUsers->handle($request));
    }

    /**
     * This method will be called by the modal's create form.
     */
    public function store(StoreUserRequest $request, StoreUsers $storeUsers)
    {
        $storeUsers->handle($request->validated());

        return redirect()->route('users.index')->with('success', 'Employee added successfully.');
    }

    /**
     * This method will be called by the modal's edit form.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser)
    {
        $updateUser->handle($user, $request->validated());

        return Redirect::route('users.index')->with('success', 'Employee details updated successfully.');
    }

    /**
     * This method handles user deletion.
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
}
