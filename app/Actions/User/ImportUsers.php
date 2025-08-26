<?php

// MAKE SURE THIS NAMESPACE IS CORRECT

namespace App\Actions\User;

use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException; // <-- ADD THIS LINE

class ImportUsers
{
    /**
     * Handle the web request to import users from an uploaded file.
     */
    public function handle(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        try {
            Excel::import(new UsersImport, $request->file('file'));
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->failures());
        }

        return Redirect::route('users.index')->with('success', 'Users imported successfully!');
    }
}
