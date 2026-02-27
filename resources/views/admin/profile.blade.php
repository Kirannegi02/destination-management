@extends('admin.layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 class="card-title" style="margin-bottom: 4px;">Profile</h2>
                <p style="color: #718096; font-size: 14px;">View your email and change your password.</p>
            </div>
        </div>

        <div style="padding: 20px; max-width: 520px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #2d3748;">Email</label>
                <div style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f7fafc; color: #4a5568;">
                    {{ $admin->email }}
                </div>
            </div>

            <form action="{{ route('admin.profile.password') }}" method="POST">
                @csrf
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #2d3748;">Current Password</label>
                    <input type="password" name="current_password" required
                           style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('current_password')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #2d3748;">New Password</label>
                    <input type="password" name="new_password" required
                           style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('new_password')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #2d3748;">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" required
                           style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                </div>

                <button type="submit"
                        style="padding: 12px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    Update Password
                </button>
            </form>
        </div>
    </div>
@endsection

