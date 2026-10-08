<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except(['current_password', 'password']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);
        $request->session()->put('locale', $user->locale);

        return back()->with('status', __('support.flash.profile_saved'));
    }
}
