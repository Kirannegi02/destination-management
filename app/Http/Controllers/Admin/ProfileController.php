<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Show admin profile page.
     */
    public function edit()
    {
        $admin = auth('admin')->user();
        return view('admin.profile', compact('admin'));
    }

    /**
     * Update admin password.
     */
    public function updatePassword(Request $request)
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        $data = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $admin->password)) {
            return back()->with('error', 'Current password is incorrect.')->withInput();
        }

        $admin->password = Hash::make($data['new_password']);
        $admin->save();

        return back()->with('success', 'Password updated successfully.');
    }
}

