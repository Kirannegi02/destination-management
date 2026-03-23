@extends('admin.layouts.app')

@section('title', 'Edit Guide')
@section('page-title', 'Edit Guide')

@section('content')
    @php
        $availableDaysOld = (array) old('available_days', $guide->available_days ?? []);
        $packages = old('packages', $guide->packages->toArray());
    @endphp
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
            <h2 class="card-title">Edit Guide</h2>
            <a href="{{ route('admin.guides.index') }}" style="color:#667eea; text-decoration:none; font-size:14px;">← Back to Guides List</a>
        </div>

        @if($errors->any())
            <div style="background:#f8d7da; color:#721c24; padding:12px; border-radius:6px; margin:20px; border:1px solid #f5c6cb;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin:8px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.guides.update', $guide->id) }}" method="POST" enctype="multipart/form-data" style="max-width:1200px; padding:20px;">
            @csrf
            @method('PUT')

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:20px;">Basic Profile</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Full Name <span style="color:#e53e3e;">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name', $guide->full_name) }}" required style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Guide Title (Listing)</label>
                        <input type="text" name="title" value="{{ old('title', $guide->title) }}" placeholder="Ex: 3H City Tour Specialist" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Profile Photo</label>
                        <input type="file" name="profile_photo" accept="image/*" style="width:100%; padding:8px; border:2px solid #e2e8f0; border-radius:8px;">
                        @if($guide->profile_photo)
                            <small style="display:block; margin-top:6px;">Current: <a href="{{ url('storage/app/public/' . ltrim($guide->profile_photo, '/')) }}" target="_blank">View</a></small>
                        @endif
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Gender</label>
                        <select name="gender" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                            <option value="">Select</option>
                            <option value="male" {{ old('gender', $guide->gender) == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $guide->gender) == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $guide->gender) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($guide->date_of_birth)->format('Y-m-d')) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Status <span style="color:#e53e3e;">*</span></label>
                        <select name="status" required style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                            <option value="active" {{ old('status', $guide->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $guide->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="display_on_website" value="1" {{ old('display_on_website', $guide->display_on_website) ? 'checked' : '' }}> Display on website
                        </label>
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="featured_guide" value="1" {{ old('featured_guide', $guide->featured_guide) ? 'checked' : '' }}> Featured guide
                        </label>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:20px;">Contact & Identity</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px;">
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Phone Country Code</label><input type="text" name="phone_country_code" value="{{ old('phone_country_code', $guide->phone_country_code) }}" placeholder="+91" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Phone Number</label><input type="text" name="phone_number" value="{{ old('phone_number', $guide->phone_number) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Email</label><input type="email" name="email" value="{{ old('email', $guide->email) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">WhatsApp</label><input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $guide->whatsapp_number) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Emergency Contact</label><input type="text" name="emergency_contact_number" value="{{ old('emergency_contact_number', $guide->emergency_contact_number) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Nationality</label><input type="text" name="nationality" value="{{ old('nationality', $guide->nationality) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Years of Experience</label><input type="number" name="years_experience" value="{{ old('years_experience', $guide->years_experience) }}" min="0" max="80" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Verification Status</label>
                        <select name="verification_status" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                            <option value="approved" {{ old('verification_status', $guide->verification_status) == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ old('verification_status', $guide->verification_status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:15px;">
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Short Bio</label>
                    <textarea name="short_bio" rows="3" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">{{ old('short_bio', $guide->short_bio) }}</textarea>
                </div>
            </div>

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:20px;">Location</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px;">
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Country</label><input type="text" name="country" value="{{ old('country', $guide->country) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">City</label><input type="text" name="city" value="{{ old('city', $guide->city) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                </div>
            </div>

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:20px;">Language & Proficiency</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:20px;">
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Primary Language</label><input type="text" name="primary_language" value="{{ old('primary_language', $guide->primary_language) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Other Languages (comma separated)</label><input type="text" name="other_languages" value="{{ old('other_languages', implode(',', $guide->other_languages ?? [])) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Proficiency Level</label>
                        <select name="language_proficiency" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                            <option value="">Select</option>
                            <option value="basic" {{ old('language_proficiency', $guide->language_proficiency) == 'basic' ? 'selected' : '' }}>Basic</option>
                            <option value="intermediate" {{ old('language_proficiency', $guide->language_proficiency) == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                            <option value="fluent" {{ old('language_proficiency', $guide->language_proficiency) == 'fluent' ? 'selected' : '' }}>Fluent</option>
                            <option value="native" {{ old('language_proficiency', $guide->language_proficiency) == 'native' ? 'selected' : '' }}>Native</option>
                        </select>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:20px;">Availability & Timing</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px;">
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Available From</label><input type="date" name="available_from_date" value="{{ old('available_from_date', optional($guide->available_from_date)->format('Y-m-d')) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Available To</label><input type="date" name="available_to_date" value="{{ old('available_to_date', optional($guide->available_to_date)->format('Y-m-d')) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Daily Start Time</label><input type="time" name="daily_start_time" value="{{ old('daily_start_time', optional($guide->daily_start_time)->format('H:i')) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Daily End Time</label><input type="time" name="daily_end_time" value="{{ old('daily_end_time', optional($guide->daily_end_time)->format('H:i')) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                </div>
                <div style="margin:15px 0; display:flex; gap:12px; flex-wrap:wrap;">
                    @php $days = ['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun']; @endphp
                    @foreach($days as $key=>$label)
                        <label style="display:flex; align-items:center; gap:6px; background:#f7fafc; padding:8px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                            <input type="checkbox" name="available_days[]" value="{{ $key }}" {{ in_array($key, $availableDaysOld) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:20px;">
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Max Bookings / Day</label><input type="number" name="max_bookings_per_day" value="{{ old('max_bookings_per_day', $guide->max_bookings_per_day) }}" min="1" max="500" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                </div>
            </div>

            

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:20px;">Documents & Verification</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:20px;">
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">ID Proof Type</label><input type="text" name="id_proof_type" value="{{ old('id_proof_type', $guide->id_proof_type) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">ID Proof Number</label><input type="text" name="id_proof_number" value="{{ old('id_proof_number', $guide->id_proof_number) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">ID Proof Upload</label>
                        <input type="file" name="id_proof_upload" accept=".jpg,.jpeg,.png,.pdf" style="width:100%; padding:8px; border:2px solid #e2e8f0; border-radius:8px;">
                        @if($guide->id_proof_path)
                            <small style="display:block; margin-top:6px;">Current: <a href="{{ url('storage/app/public/' . ltrim($guide->id_proof_path, '/')) }}" target="_blank">View</a></small>
                        @endif
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Guide License / Certification</label>
                        <input type="file" name="license_upload" accept=".jpg,.jpeg,.png,.pdf" style="width:100%; padding:8px; border:2px solid #e2e8f0; border-radius:8px;">
                        @if($guide->license_path)
                            <small style="display:block; margin-top:6px;">Current: <a href="{{ url('storage/app/public/' . ltrim($guide->license_path, '/')) }}" target="_blank">View</a></small>
                        @endif
                    </div>
                    <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                        <input type="checkbox" name="police_verification" value="1" {{ old('police_verification', $guide->police_verification) ? 'checked' : '' }}> Police verification completed
                    </label>
                </div>
            </div>

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:20px;">Experience with Indian Customers</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px;">
                    <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                        <input type="checkbox" name="experience_indian_customers" value="1" {{ old('experience_indian_customers', $guide->experience_indian_customers) ? 'checked' : '' }}> Experienced with Indian customers
                    </label>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Indian Tours Completed</label><input type="number" name="indian_tours_completed" value="{{ old('indian_tours_completed', $guide->indian_tours_completed) }}" min="0" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                    <div><label style="display:block; margin-bottom:6px; font-weight:600;">Indian Language Support (comma separated)</label><input type="text" name="indian_language_support" value="{{ old('indian_language_support', implode(',', $guide->indian_language_support ?? [])) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;"></div>
                </div>
                <div style="margin-top:12px;">
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Special Notes for Indian Travelers</label>
                    <textarea name="indian_special_notes" rows="2" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">{{ old('indian_special_notes', $guide->indian_special_notes) }}</textarea>
                </div>
            </div>

            

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:12px;">Service Packages (multiple)</h3>
                <p style="margin-bottom:10px; color:#4a5568;">Add multiple packages (3h / 6h / 8h / 12h). Click “Add Package” to append more rows.</p>
                <div id="package-rows" data-package-index-start="{{ count($packages) ?: 1 }}" style="display:flex; flex-direction:column; gap:12px;">
                    @forelse($packages as $index => $pkg)
                        <div class="package-row" style="border:1px solid #e2e8f0; border-radius:8px; padding:12px; background:#f9fafb;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <strong>Package #{{ $index + 1 }}</strong>
                                <button type="button" onclick="this.closest('.package-row').remove()" style="background:none; color:#e53e3e; border:none; font-weight:700; cursor:pointer;">Remove</button>
                            </div>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:10px;">
                                <input type="hidden" name="packages[{{ $index }}][id]" value="{{ $pkg['id'] ?? '' }}">
                                <div><label>Service Type</label>
                                    <select name="packages[{{ $index }}][service_type]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;">
                                        <option value="">Select</option>
                                        <option value="3h" {{ ($pkg['service_type'] ?? '') === '3h' ? 'selected' : '' }}>3 Hour</option>
                                        <option value="6h" {{ ($pkg['service_type'] ?? '') === '6h' ? 'selected' : '' }}>6 Hour</option>
                                        <option value="8h" {{ ($pkg['service_type'] ?? '') === '8h' ? 'selected' : '' }}>8 Hour</option>
                                        <option value="12h" {{ ($pkg['service_type'] ?? '') === '12h' ? 'selected' : '' }}>12 Hour</option>
                                    </select>
                                </div>
                                <div><label>Service Name</label><input type="text" name="packages[{{ $index }}][service_name]" value="{{ $pkg['service_name'] ?? '' }}" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Duration (hrs)</label><input type="number" name="packages[{{ $index }}][duration_hours]" value="{{ $pkg['duration_hours'] ?? '' }}" min="1" max="24" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Currency</label><input type="text" name="packages[{{ $index }}][currency]" value="{{ $pkg['currency'] ?? 'INR' }}" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Price</label><input type="number" name="packages[{{ $index }}][standard_price]" value="{{ $pkg['standard_price'] ?? '' }}" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Extra Hour Price</label><input type="number" name="packages[{{ $index }}][extra_hour_price]" value="{{ $pkg['extra_hour_price'] ?? '' }}" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Default Start Location</label><input type="text" name="packages[{{ $index }}][default_start_location]" value="{{ $pkg['default_start_location'] ?? '' }}" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Default End Location</label><input type="text" name="packages[{{ $index }}][default_end_location]" value="{{ $pkg['default_end_location'] ?? '' }}" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Meeting Point (Start)</label><input type="text" name="packages[{{ $index }}][start_point]" value="{{ $pkg['start_point'] ?? '' }}" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Drop-off Point (End)</label><input type="text" name="packages[{{ $index }}][end_point]" value="{{ $pkg['end_point'] ?? '' }}" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Start Time</label><input type="time" name="packages[{{ $index }}][start_time]" value="{{ isset($pkg['start_time']) && $pkg['start_time'] ? \Illuminate\Support\Str::of((string)$pkg['start_time'])->substr(0,5) : '' }}" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>End Time</label><input type="time" name="packages[{{ $index }}][end_time]" value="{{ isset($pkg['end_time']) && $pkg['end_time'] ? \Illuminate\Support\Str::of((string)$pkg['end_time'])->substr(0,5) : '' }}" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div>
                                    <label>Status</label>
                                    <select name="packages[{{ $index }}][status]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;">
                                        <option value="active" {{ (($pkg['status'] ?? null) === 'inactive' || array_key_exists('active', $pkg) && !$pkg['active']) ? '' : 'selected' }}>Active</option>
                                        <option value="inactive" {{ (($pkg['status'] ?? null) === 'inactive' || array_key_exists('active', $pkg) && !$pkg['active']) ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div style="margin-top:10px;">
                                <label>Notes</label>
                                <textarea name="packages[{{ $index }}][notes]" rows="2" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;">{{ $pkg['notes'] ?? $pkg['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    @empty
                        <div class="package-row" style="border:1px solid #e2e8f0; border-radius:8px; padding:12px; background:#f9fafb;">
                            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:10px;">
                                <div><label>Service Type</label>
                                    <select name="packages[0][service_type]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;">
                                        <option value="">Select</option>
                                        <option value="3h">3 Hour</option>
                                        <option value="6h">6 Hour</option>
                                        <option value="8h">8 Hour</option>
                                        <option value="12h">12 Hour</option>
                                    </select>
                                </div>
                                <div><label>Service Name</label><input type="text" name="packages[0][service_name]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Duration (hrs)</label><input type="number" name="packages[0][duration_hours]" min="1" max="24" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Currency</label><input type="text" name="packages[0][currency]" value="INR" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Price</label><input type="number" name="packages[0][standard_price]" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Extra Hour Price</label><input type="number" name="packages[0][extra_hour_price]" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Default Start Location</label><input type="text" name="packages[0][default_start_location]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Default End Location</label><input type="text" name="packages[0][default_end_location]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Meeting Point (Start)</label><input type="text" name="packages[0][start_point]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Drop-off Point (End)</label><input type="text" name="packages[0][end_point]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>Start Time</label><input type="time" name="packages[0][start_time]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div><label>End Time</label><input type="time" name="packages[0][end_time]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                                <div>
                                    <label>Status</label>
                                    <select name="packages[0][status]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;">
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label>Notes</label>
                                <textarea name="packages[0][notes]" rows="2" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></textarea>
                            </div>
                        </div>
                    @endforelse
                </div>
                <button type="button" onclick="addPackageRow()" style="margin-top:10px; background:#e2e8f0; color:#2d3748; padding:10px 14px; border:none; border-radius:6px; cursor:pointer;">+ Add Package</button>
            </div>

            <div style="display:flex; gap:12px; margin-top:30px;">
                <button type="submit" style="background:#667eea; color:white; padding:12px 24px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;">Update Guide</button>
                <a href="{{ route('admin.guides.index') }}" style="background:#e2e8f0; color:#2d3748; padding:12px 24px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:600; display:inline-block;">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        let packageIndex = Number(document.getElementById('package-rows')?.dataset?.packageIndexStart || 1);
        function addPackageRow() {
            const container = document.getElementById('package-rows');
            const idx = packageIndex++;
            const html = `
            <div class="package-row" style="border:1px solid #e2e8f0; border-radius:8px; padding:12px; background:#fdfdfd;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <strong>Package #${idx+1}</strong>
                    <button type="button" onclick="this.closest('.package-row').remove()" style="background:none; color:#e53e3e; border:none; font-weight:700; cursor:pointer;">Remove</button>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:10px;">
                    <div><label>Service Type</label>
                        <select name="packages[${idx}][service_type]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;">
                            <option value="">Select</option>
                            <option value="3h">3 Hour</option>
                            <option value="6h">6 Hour</option>
                            <option value="8h">8 Hour</option>
                            <option value="12h">12 Hour</option>
                        </select>
                    </div>
                    <div><label>Service Name</label><input type="text" name="packages[${idx}][service_name]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>Duration (hrs)</label><input type="number" name="packages[${idx}][duration_hours]" min="1" max="24" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>Currency</label><input type="text" name="packages[${idx}][currency]" value="INR" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>Price</label><input type="number" name="packages[${idx}][standard_price]" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>Extra Hour Price</label><input type="number" name="packages[${idx}][extra_hour_price]" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>Default Start Location</label><input type="text" name="packages[${idx}][default_start_location]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>Default End Location</label><input type="text" name="packages[${idx}][default_end_location]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>Meeting Point (Start)</label><input type="text" name="packages[${idx}][start_point]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>Drop-off Point (End)</label><input type="text" name="packages[${idx}][end_point]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>Start Time</label><input type="time" name="packages[${idx}][start_time]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div><label>End Time</label><input type="time" name="packages[${idx}][end_time]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></div>
                    <div>
                        <label>Status</label>
                        <select name="packages[${idx}][status]" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:10px;">
                    <label>Notes</label>
                    <textarea name="packages[${idx}][notes]" rows="2" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"></textarea>
                </div>
            </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }
    </script>
@endsection
