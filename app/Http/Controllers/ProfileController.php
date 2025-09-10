<?php

namespace App\Http\Controllers;

use App\Actions\Profile\DeleteAccount;
use App\Actions\Profile\EditProfile;
use App\Actions\Profile\UpdateProfile;
use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request, EditProfile $getProfileData): Response
    {
        return Inertia::render('Profile/Edit', $getProfileData->handle($request));
    }

    public function update(ProfileUpdateRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle($request);

        return redirect()->route('profile.edit');
    }

    public function destroy(DeleteAccountRequest $request, DeleteAccount $deleteAccount): RedirectResponse
    {
        $deleteAccount->handle($request);

        return Redirect::to('/');
    }
}
