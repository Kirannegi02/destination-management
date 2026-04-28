@php
    $isEdit  = !is_null($guide);

    // Generic field value helper
    $v = fn(string $field, $default = '') => old($field, $isEdit ? ($guide->{$field} ?? $default) : $default);

    // Date fields: format Carbon objects as Y-m-d for <input type="date">
    $vDate = function(string $field) use ($isEdit, $guide) {
        if (old($field)) return old($field);
        if (!$isEdit) return '';
        $val = $guide->{$field} ?? null;
        if (!$val) return '';
        return ($val instanceof \Carbon\Carbon) ? $val->format('Y-m-d') : substr((string) $val, 0, 10);
    };

    // Time fields: format as H:i for <input type="time">
    $vTime = function(string $field) use ($isEdit, $guide) {
        if (old($field)) return old($field);
        if (!$isEdit) return '';
        $val = $guide->{$field} ?? null;
        if (!$val) return '';
        if ($val instanceof \Carbon\Carbon) return $val->format('H:i');
        // stored as "HH:MM:SS" string — trim to HH:MM
        return substr((string) $val, 0, 5);
    };

    $inp     = 'width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;';
    $section = 'background:#f7fafc;padding:20px;border-radius:10px;margin-bottom:24px;';
    $h3      = 'font-size:16px;font-weight:700;color:#2d3748;margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid #e2e8f0;';
@endphp

<style>
    .fg { margin-bottom: 16px; }
    .fg label { display:block; font-weight:600; font-size:13px; color:#4a5568; margin-bottom:6px; }
    .fg input, .fg select, .fg textarea { width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px; background:#fff; }
    .fg textarea { resize:vertical; min-height:90px; }
    .fg .err { color:#e53e3e; font-size:12px; margin-top:4px; }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
    @media(max-width:640px){ .grid2,.grid3{ grid-template-columns:1fr; } }
    .section-card { background:#f7fafc; padding:20px; border-radius:10px; margin-bottom:24px; }
    .section-title { font-size:16px; font-weight:700; color:#2d3748; margin-bottom:16px; padding-bottom:8px; border-bottom:2px solid #e2e8f0; }
    .check-label { display:flex; align-items:center; gap:8px; font-size:14px; color:#4a5568; cursor:pointer; }
    .check-label input[type=checkbox]{ width:16px; height:16px; }
    .btn-submit {
        padding:12px 32px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
        color:#fff; border:none; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer;
    }
    .btn-submit:hover{ opacity:.92; }
    .form-header { display:flex; justify-content:space-between; align-items:center; padding:16px 20px;
        background:#fff; border-radius:10px; margin-bottom:20px;
        box-shadow:0 1px 4px rgba(0,0,0,.06); }
</style>

<div class="form-header">
    <div>
        <h2 style="font-size:20px;font-weight:700;color:#1a202c;margin:0;">
            {{ $isEdit ? 'Edit Guide: '.($guide->full_name ?? $guide->title) : 'Add New Guide' }}
        </h2>
        @if($isEdit)
            <span style="font-size:13px;color:#718096;">ID #{{ $guide->id }}</span>
        @endif
    </div>
    <a href="{{ route('admin.guides.index') }}" style="color:#667eea;text-decoration:none;font-size:14px;">
        ← Back to Guides
    </a>
</div>

@if(session('error'))
    <div style="background:#fff5f5;color:#c53030;padding:14px 18px;border-radius:8px;margin-bottom:20px;font-size:14px;">
        <strong>Error:</strong> {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div style="background:#fff5f5;color:#c53030;padding:14px 18px;border-radius:8px;margin-bottom:20px;font-size:14px;">
        <strong>Please fill in the required fields:</strong>
        <ul style="margin:8px 0 0;padding-left:20px;">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    {{-- ── BASIC INFORMATION ─────────────────────────────────────────────── --}}
    <div class="section-card">
        <div class="section-title">Basic Information</div>

        <div class="grid2">
            <div class="fg">
                <label>Service Title <span style="color:#e53e3e">*</span></label>
                <input type="text" name="title" value="{{ $v('title') }}" placeholder="e.g. Delhi City Tour" required>
                @error('title')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Full Name <span style="color:#e53e3e">*</span></label>
                <input type="text" name="full_name" value="{{ $v('full_name') }}" placeholder="Guide's full name" required>
                @error('full_name')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid3">
            <div class="fg">
                <label>Gender</label>
                <select name="gender">
                    <option value="">— Select —</option>
                    @foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $k=>$l)
                        <option value="{{ $k }}" {{ $v('gender') == $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('gender')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" value="{{ $vDate('date_of_birth') }}">
                @error('date_of_birth')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Nationality</label>
                <input type="text" name="nationality" value="{{ $v('nationality') }}" placeholder="e.g. Indian">
                @error('nationality')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid2">
            <div class="fg">
                <label>Years of Experience</label>
                <input type="number" name="years_experience" value="{{ $v('years_experience') }}" min="0" max="80" placeholder="0">
                @error('years_experience')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Status <span style="color:#e53e3e">*</span></label>
                <select name="status" required>
                    <option value="active"  {{ $v('status','active') == 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive"{{ $v('status','active') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="fg">
            <label>Profile Photo</label>
            @if($isEdit && $guide->profile_photo)
                @php $photoUrl = \App\Services\ImageService::getUrl($guide->profile_photo); @endphp
                @if($photoUrl)
                    <div style="margin-bottom:10px;">
                        <img src="{{ $photoUrl }}" alt="Profile"
                             style="height:100px;border-radius:8px;border:1px solid #e2e8f0;object-fit:cover;"
                             onerror="this.style.display='none'">
                        <div style="font-size:11px;color:#718096;margin-top:4px;word-break:break-all;">{{ $photoUrl }}</div>
                    </div>
                @endif
            @endif
            <div style="margin-bottom:8px;">
                <label style="font-weight:500;font-size:13px;">Upload new photo (file)</label>
                <input type="file" name="profile_photo" accept="image/*" style="display:block;margin-top:4px;">
                @error('profile_photo')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div>
                <label style="font-weight:500;font-size:13px;">Or paste photo URL</label>
                <input type="url" name="profile_photo_url"
                       value="{{ old('profile_photo_url') }}"
                       placeholder="https://example.com/photo.jpg"
                       style="display:block;margin-top:4px;width:100%;padding:8px;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;">
                <div style="font-size:11px;color:#718096;margin-top:3px;">File upload takes priority over URL if both are provided.</div>
            </div>
        </div>

        <div class="fg">
            <label>Short Bio</label>
            <textarea name="short_bio" placeholder="A brief 1-2 sentence introduction">{{ $v('short_bio') }}</textarea>
            @error('short_bio')<div class="err">{{ $message }}</div>@enderror
        </div>

        <div class="fg">
            <label>Full Description</label>
            <textarea name="description" rows="4" placeholder="Detailed description of this guide's experience">{{ $v('description') }}</textarea>
            @error('description')<div class="err">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- ── CONTACT ────────────────────────────────────────────────────────── --}}
    <div class="section-card">
        <div class="section-title">Contact Information</div>

        <div class="grid3">
            <div class="fg">
                <label>Country Code</label>
                <input type="text" name="phone_country_code" value="{{ $v('phone_country_code') }}" placeholder="+91">
                @error('phone_country_code')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Phone Number</label>
                <input type="text" name="phone_number" value="{{ $v('phone_number') }}" placeholder="9876543210">
                @error('phone_number')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>WhatsApp Number</label>
                <input type="text" name="whatsapp_number" value="{{ $v('whatsapp_number') }}" placeholder="9876543210">
                @error('whatsapp_number')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid2">
            <div class="fg">
                <label>Email</label>
                <input type="email" name="email" value="{{ $v('email') }}" placeholder="guide@example.com">
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Emergency Contact Number</label>
                <input type="text" name="emergency_contact_number" value="{{ $v('emergency_contact_number') }}" placeholder="+91-9000000000">
                @error('emergency_contact_number')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ── LOCATION & LANGUAGE ─────────────────────────────────────────────── --}}
    <div class="section-card">
        <div class="section-title">Location & Language</div>

        <div class="grid2">
            <div class="fg">
                <label>Country</label>
                <input type="text" name="country" value="{{ $v('country') }}" placeholder="India">
                @error('country')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>City</label>
                <input type="text" name="city" value="{{ $v('city') }}" placeholder="Delhi">
                @error('city')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid3">
            <div class="fg">
                <label>Primary Language</label>
                <input type="text" name="primary_language" value="{{ $v('primary_language') }}" placeholder="English">
                @error('primary_language')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Language (display)</label>
                <input type="text" name="language" value="{{ $v('language') }}" placeholder="English">
                @error('language')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Proficiency Level</label>
                <input type="text" name="language_proficiency" value="{{ $v('language_proficiency') }}" placeholder="Fluent">
                @error('language_proficiency')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="fg">
            <label>Other Languages <small style="font-weight:400;color:#718096;">(comma-separated)</small></label>
            <input type="text" name="other_languages"
                   value="{{ $isEdit && is_array($guide->other_languages) ? implode(', ', $guide->other_languages) : old('other_languages') }}"
                   placeholder="Hindi, French, Spanish">
            @error('other_languages')<div class="err">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- ── AVAILABILITY ────────────────────────────────────────────────────── --}}
    <div class="section-card">
        <div class="section-title">Availability</div>

        <div class="grid2">
            <div class="fg">
                <label>Available From</label>
                <input type="date" name="available_from_date" value="{{ $vDate('available_from_date') }}">
                @error('available_from_date')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Available To</label>
                <input type="date" name="available_to_date" value="{{ $vDate('available_to_date') }}">
                @error('available_to_date')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid2">
            <div class="fg">
                <label>Daily Start Time</label>
                <input type="time" name="daily_start_time" value="{{ $vTime('daily_start_time') }}">
                @error('daily_start_time')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Daily End Time</label>
                <input type="time" name="daily_end_time" value="{{ $vTime('daily_end_time') }}">
                @error('daily_end_time')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="fg">
            <label>Available Days <small style="font-weight:400;color:#718096;">(comma-separated, e.g. Monday, Tuesday)</small></label>
            <input type="text" name="available_days"
                   value="{{ $isEdit && is_array($guide->available_days) ? implode(', ', $guide->available_days) : old('available_days') }}"
                   placeholder="Monday, Tuesday, Wednesday, Thursday, Friday">
            @error('available_days')<div class="err">{{ $message }}</div>@enderror
        </div>

        <div class="fg">
            <label>Max Bookings per Day</label>
            <input type="number" name="max_bookings_per_day" value="{{ $v('max_bookings_per_day') }}" min="1" max="500" placeholder="5">
            @error('max_bookings_per_day')<div class="err">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- ── PRICING ─────────────────────────────────────────────────────────── --}}
    <div class="section-card">
        <div class="section-title">Pricing (EUR)</div>
        <div class="grid3">
            <div class="fg">
                <label>Half Day Price (€)</label>
                <input type="number" name="half_day_price" value="{{ $v('half_day_price') }}" step="0.01" min="0" placeholder="0.00">
                @error('half_day_price')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Full Day Price (€)</label>
                <input type="number" name="full_day_price" value="{{ $v('full_day_price') }}" step="0.01" min="0" placeholder="0.00">
                @error('full_day_price')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Extra Hour Price (€)</label>
                <input type="number" name="extra_hour_price" value="{{ $v('extra_hour_price') }}" step="0.01" min="0" placeholder="0.00">
                @error('extra_hour_price')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ── ID & VERIFICATION ───────────────────────────────────────────────── --}}
    <div class="section-card">
        <div class="section-title">ID & Verification</div>

        <div class="grid2">
            <div class="fg">
                <label>ID Proof Type</label>
                <input type="text" name="id_proof_type" value="{{ $v('id_proof_type') }}" placeholder="Passport / Aadhar / License">
                @error('id_proof_type')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>ID Proof Number</label>
                <input type="text" name="id_proof_number" value="{{ $v('id_proof_number') }}" placeholder="ID number">
                @error('id_proof_number')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid2">
            <div class="fg">
                <label>ID Proof Upload <small style="font-weight:400;color:#718096;">(JPG, PNG, PDF — max 8 MB)</small></label>
                @if($isEdit && $guide->id_proof_path)
                    @php
                        $idProofUrl = \App\Services\ImageService::getUrl($guide->id_proof_path);
                        $idProofIsPdf = $idProofUrl && str_ends_with(strtolower(parse_url($idProofUrl, PHP_URL_PATH) ?? ''), '.pdf');
                    @endphp
                    @if($idProofUrl)
                        <div style="margin-bottom:8px;">
                            @if($idProofIsPdf)
                                <a href="{{ $idProofUrl }}" target="_blank"
                                   style="display:inline-flex;align-items:center;gap:6px;color:#2b6cb0;font-size:13px;text-decoration:none;padding:6px 10px;border:1px solid #bee3f8;border-radius:6px;background:#ebf8ff;">
                                    📄 View ID Proof (PDF)
                                </a>
                            @else
                                <img src="{{ $idProofUrl }}" alt="ID Proof"
                                     style="height:90px;border-radius:8px;border:1px solid #e2e8f0;object-fit:cover;"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                                <a href="{{ $idProofUrl }}" target="_blank"
                                   style="display:none;align-items:center;gap:6px;color:#2b6cb0;font-size:13px;text-decoration:none;padding:6px 10px;border:1px solid #bee3f8;border-radius:6px;background:#ebf8ff;">
                                    🖼 View ID Proof
                                </a>
                            @endif
                        </div>
                    @endif
                @endif
                <input type="file" name="id_proof_upload" accept=".jpg,.jpeg,.png,.pdf">
                @error('id_proof_upload')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>License Upload <small style="font-weight:400;color:#718096;">(JPG, PNG, PDF — max 8 MB)</small></label>
                @if($isEdit && $guide->license_path)
                    @php
                        $licenseUrl = \App\Services\ImageService::getUrl($guide->license_path);
                        $licenseIsPdf = $licenseUrl && str_ends_with(strtolower(parse_url($licenseUrl, PHP_URL_PATH) ?? ''), '.pdf');
                    @endphp
                    @if($licenseUrl)
                        <div style="margin-bottom:8px;">
                            @if($licenseIsPdf)
                                <a href="{{ $licenseUrl }}" target="_blank"
                                   style="display:inline-flex;align-items:center;gap:6px;color:#2b6cb0;font-size:13px;text-decoration:none;padding:6px 10px;border:1px solid #bee3f8;border-radius:6px;background:#ebf8ff;">
                                    📄 View License (PDF)
                                </a>
                            @else
                                <img src="{{ $licenseUrl }}" alt="License"
                                     style="height:90px;border-radius:8px;border:1px solid #e2e8f0;object-fit:cover;"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                                <a href="{{ $licenseUrl }}" target="_blank"
                                   style="display:none;align-items:center;gap:6px;color:#2b6cb0;font-size:13px;text-decoration:none;padding:6px 10px;border:1px solid #bee3f8;border-radius:6px;background:#ebf8ff;">
                                    🖼 View License
                                </a>
                            @endif
                        </div>
                    @endif
                @endif
                <input type="file" name="license_upload" accept=".jpg,.jpeg,.png,.pdf">
                @error('license_upload')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid2" style="margin-top:8px;">
            <label class="check-label">
                <input type="checkbox" name="police_verification" value="1"
                    {{ ($isEdit ? $guide->police_verification : old('police_verification')) ? 'checked' : '' }}>
                Police Verification Done
            </label>
            <div class="fg" style="margin-bottom:0;">
                <label>Verification Status</label>
                <select name="verification_status">
                    <option value="pending" {{ ($v('verification_status') ?: 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $v('verification_status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ $v('verification_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                @error('verification_status')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ── SETTINGS ────────────────────────────────────────────────────────── --}}
    <div class="section-card">
        <div class="section-title">Settings</div>
        <div style="display:flex;gap:24px;flex-wrap:wrap;">
            <label class="check-label">
                <input type="checkbox" name="display_on_website" value="1"
                    {{ old('display_on_website', $isEdit ? $guide->display_on_website : true) ? 'checked' : '' }}>
                Display on Website
            </label>
            <label class="check-label">
                <input type="checkbox" name="featured_guide" value="1"
                    {{ ($isEdit ? $guide->featured_guide : false) || old('featured_guide') ? 'checked' : '' }}>
                Featured Guide
            </label>
            <label class="check-label">
                <input type="checkbox" name="experience_indian_customers" value="1"
                    {{ ($isEdit ? $guide->experience_indian_customers : false) || old('experience_indian_customers') ? 'checked' : '' }}>
                Experience with Indian Customers
            </label>
        </div>

        <div class="grid2" style="margin-top:16px;">
            <div class="fg">
                <label>Indian Tours Completed</label>
                <input type="number" name="indian_tours_completed" value="{{ $v('indian_tours_completed') }}" min="0" placeholder="0">
                @error('indian_tours_completed')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Indian Language Support <small style="font-weight:400;color:#718096;">(comma-separated)</small></label>
                <input type="text" name="indian_language_support"
                       value="{{ $isEdit && is_array($guide->indian_language_support) ? implode(', ', $guide->indian_language_support) : old('indian_language_support') }}"
                       placeholder="Hindi, Tamil">
                @error('indian_language_support')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="fg">
            <label>Indian Special Notes</label>
            <textarea name="indian_special_notes" rows="2" placeholder="Any special notes for Indian customers">{{ $v('indian_special_notes') }}</textarea>
            @error('indian_special_notes')<div class="err">{{ $message }}</div>@enderror
        </div>

        <div class="fg">
            <label>Internal Notes</label>
            <textarea name="notes" rows="2" placeholder="Internal admin notes (not shown to customers)">{{ $v('notes') }}</textarea>
            @error('notes')<div class="err">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- ── SUBMIT ──────────────────────────────────────────────────────────── --}}
    <div style="display:flex;gap:12px;align-items:center;margin-top:8px;">
        <button type="submit" class="btn-submit">{{ $submitLabel }}</button>
        <a href="{{ route('admin.guides.index') }}"
           style="padding:12px 24px;border:2px solid #e2e8f0;border-radius:8px;color:#4a5568;text-decoration:none;font-size:14px;font-weight:600;">
            Cancel
        </a>
    </div>
</form>
