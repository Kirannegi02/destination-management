@extends('admin.layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Edit User (Agent)</h2>
            <a href="{{ route('admin.users.index', ['status' => $returnStatus ?? 'pending']) }}" 
               style="color: #667eea; text-decoration: none; font-size: 14px;">
                ← Back to {{ ($returnStatus ?? 'pending') == 'approved' ? 'Approved' : 'Pending' }} List
            </a>
        </div>

        @if($errors->any())
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 8px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" enctype="multipart/form-data" style="max-width: 900px;">
            @csrf
            @method('PUT')

            <!-- Image Upload -->
            <div class="form-group" style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                    Profile Image
                </label>
                @if($user->image)
                    <div style="margin-bottom: 12px;">
                        <img src="{{ \App\Services\ImageService::getUrl($user->image) }}" 
                             alt="{{ $user->name }}" 
                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                    </div>
                @endif
                <input type="file" 
                       name="image" 
                       accept="image/*"
                       style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">Max size: 2MB. Allowed: JPEG, PNG, GIF, WEBP</small>
                @error('image')
                    <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Basic Information -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Name <span style="color: #e53e3e;">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           value="{{ old('name', $user->name) }}" 
                           required
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('name')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Agency Name <span style="color: #e53e3e;">*</span>
                    </label>
                    <input type="text" 
                           name="agency_name" 
                           value="{{ old('agency_name', $user->agency_name) }}" 
                           required
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('agency_name')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Contact Information -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Email <span style="color: #e53e3e;">*</span>
                    </label>
                    <input type="email" 
                           name="email" 
                           value="{{ old('email', $user->email) }}" 
                           required
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('email')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Phone <span style="color: #e53e3e;">*</span>
                    </label>
                    <input type="text" 
                           name="phone" 
                           value="{{ old('phone', $user->phone) }}" 
                           required
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('phone')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Alternate Phone
                    </label>
                    <input type="text" 
                           name="alternate_phone" 
                           value="{{ old('alternate_phone', $user->alternate_phone) }}"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('alternate_phone')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Tax Number <span style="color: #e53e3e;">*</span>
                        </label>
                        <input type="text"
                               name="tax_number"
                               value="{{ old('tax_number', $user->tax_number) }}"
                           required
                           maxlength="15"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; text-transform: uppercase;">
                    @error('tax_number')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Address Information -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                    Address <span style="color: #e53e3e;">*</span>
                </label>
                <textarea name="address" 
                          rows="3"
                          required
                          style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('address', $user->address) }}</textarea>
                @error('address')
                    <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Country <span style="color: #e53e3e;">*</span>
                    </label>
                    <input type="text" 
                           name="country" 
                           value="{{ old('country', $user->country) }}" 
                           required
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('country')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        State <span style="color: #e53e3e;">*</span>
                    </label>
                    <input type="text" 
                           name="state" 
                           value="{{ old('state', $user->state) }}" 
                           required
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('state')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        City
                    </label>
                    <input type="text" 
                           name="city" 
                           value="{{ old('city', $user->city) }}"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('city')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Pincode
                    </label>
                    <input type="text" 
                           name="pincode" 
                           value="{{ old('pincode', $user->pincode) }}"
                           maxlength="10"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('pincode')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Status <span style="color: #e53e3e;">*</span>
                    </label>
                    <select name="status" 
                            required
                            style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="pending" {{ old('status', $user->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                    @error('status')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="submit" 
                        style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    Update User
                </button>
                <a href="{{ route('admin.users.index', ['status' => $returnStatus ?? 'pending']) }}" 
                   style="background: #e2e8f0; color: #2d3748; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-block;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection

