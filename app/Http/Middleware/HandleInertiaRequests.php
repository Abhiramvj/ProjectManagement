<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $request->user() ? [
                    // --- THE DEFINITIVE FIX IS HERE ---
                    // We manually build the user array to include exactly what we need.
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'designation' => $request->user()->designation,
                    'image' => $request->user()->image,
                    'avatar_url' => $request->user()->avatar_url,

                    // THIS IS THE CRITICAL LINE THAT PROVIDES PERMISSIONS TO THE FRONTEND
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),

                ] : null,
            ],
            'ziggy' => fn () => [
                ...(new \Tighten\Ziggy\Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
