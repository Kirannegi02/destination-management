@php
    $v = $venue;
    $oldSpaces = old('spaces', $spaces->map(fn ($s) => [
        'name' => $s->name,
        'description' => $s->description,
        'total_space_sqm' => $s->total_space_sqm,
        'length_m' => $s->length_m,
        'width_m' => $s->width_m,
        'ceiling_height_m' => $s->ceiling_height_m,
        'setup_capacities' => $s->setup_capacities ?? [],
        'amenities' => $s->amenities ?? [],
        'is_outdoor' => $s->is_outdoor,
        'is_private' => $s->is_private,
        'is_semi_private' => $s->is_semi_private,
        'wheelchair_accessible' => $s->wheelchair_accessible,
        'sort_order' => $s->sort_order,
        'status' => $s->status,
    ])->values()->all());
    if (empty($oldSpaces)) {
        $oldSpaces = [['name' => '', 'setup_capacities' => [], 'amenities' => [], 'is_private' => true, 'status' => 'active']];
    }
    $selectedAmenities = old('amenities', $v?->amenities ?? []);
    $selectedEventTypes = old('event_types', $v?->event_types ?? []);
@endphp

<div style="margin-bottom:30px;">
    <h3 style="color:#2d3748;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:16px;">Venue profile</h3>
    <p style="color:#718096;font-size:13px;margin-bottom:16px;">Core listing information — similar to a Cvent venue profile.</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px;">
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Venue name <span style="color:#e53e3e;">*</span></label>
            <input type="text" name="name" value="{{ old('name', $v?->name) }}" required
                   style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Venue type <span style="color:#e53e3e;">*</span></label>
            <select name="venue_type" required style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
                @foreach($venueTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('venue_type', $v?->venue_type ?? 'conference_center') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Brand / chain</label>
            <input type="text" name="brand_chain" value="{{ old('brand_chain', $v?->brand_chain) }}" placeholder="e.g. Hyatt, Marriott"
                   style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Star rating</label>
            <select name="star_rating" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
                <option value="">—</option>
                @for($i = 1; $i <= 5; $i++)
                    <option value="{{ $i }}" {{ old('star_rating', $v?->star_rating) == $i ? 'selected' : '' }}>{{ $i }} star</option>
                @endfor
            </select>
        </div>
    </div>
    <div style="margin-bottom:16px;">
        <label style="font-weight:600;display:block;margin-bottom:6px;">Description</label>
        <textarea name="description" rows="4" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">{{ old('description', $v?->description) }}</textarea>
    </div>
    <div style="margin-bottom:16px;">
        <label style="font-weight:600;display:block;margin-bottom:6px;">Highlights (short summary for listings)</label>
        <textarea name="highlights" rows="2" placeholder="e.g. 100,000 sq ft meeting space, renovated ballrooms..."
                  style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">{{ old('highlights', $v?->highlights) }}</textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Status <span style="color:#e53e3e;">*</span></label>
            <select name="status" required style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
                @foreach(['active','pending','inactive'] as $st)
                    <option value="{{ $st }}" {{ old('status', $v?->status ?? 'active') == $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-weight:600;display:block;margin-bottom:6px;">Display order</label>
            <input type="number" name="display_order" min="0" value="{{ old('display_order', $v?->display_order ?? 0) }}"
                   style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
        </div>
        <div style="display:flex;align-items:end;padding-bottom:8px;">
            <label style="display:flex;align-items:center;gap:8px;font-weight:600;cursor:pointer;">
                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $v?->is_featured) ? 'checked' : '' }} style="width:18px;height:18px;">
                Featured venue
            </label>
        </div>
    </div>
</div>

<div style="margin-bottom:30px;">
    <h3 style="color:#2d3748;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:16px;">Location & contact</h3>
    <div style="margin-bottom:16px;">
        <label style="font-weight:600;display:block;margin-bottom:6px;">Full address <span style="color:#e53e3e;">*</span></label>
        <textarea name="address" rows="2" required style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">{{ old('address', $v?->address) }}</textarea>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:16px;">
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">City</label>
            <input type="text" name="city" value="{{ old('city', $v?->city) }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">State / region</label>
            <input type="text" name="state" value="{{ old('state', $v?->state) }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Country</label>
            <input type="text" name="country" value="{{ old('country', $v?->country) }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Postal code</label>
            <input type="text" name="pincode" value="{{ old('pincode', $v?->pincode) }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Latitude</label>
            <input type="text" name="latitude" value="{{ old('latitude', $v?->latitude) }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Longitude</label>
            <input type="text" name="longitude" value="{{ old('longitude', $v?->longitude) }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $v?->phone) }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Email</label>
            <input type="email" name="email" value="{{ old('email', $v?->email) }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Contact name</label>
            <input type="text" name="contact_name" value="{{ old('contact_name', $v?->contact_name) }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
    </div>
    <div style="margin-top:16px;">
        <label style="font-weight:600;display:block;margin-bottom:6px;">Website</label>
        <input type="url" name="website" value="{{ old('website', $v?->website) }}" placeholder="https://"
               style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
    </div>
</div>

<div style="margin-bottom:30px;">
    <h3 style="color:#2d3748;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:16px;">Event size & capacity (search filters)</h3>
    <p style="color:#718096;font-size:13px;margin-bottom:12px;">Matches Cvent search: location, event dates (on frontend), and event size.</p>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Min event size (guests)</label>
            <input type="number" name="min_event_size" min="1" value="{{ old('min_event_size', $v?->min_event_size) }}"
                   style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Max event size (guests)</label>
            <input type="number" name="max_event_size" min="1" value="{{ old('max_event_size', $v?->max_event_size) }}"
                   style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Sleeping rooms (hotels)</label>
            <input type="number" name="sleeping_rooms" min="0" value="{{ old('sleeping_rooms', $v?->sleeping_rooms) }}"
                   style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Number of meeting rooms</label>
            <input type="number" name="number_of_meeting_rooms" min="0" value="{{ old('number_of_meeting_rooms', $v?->number_of_meeting_rooms) }}"
                   style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
    </div>
</div>

<div style="margin-bottom:30px;">
    <h3 style="color:#2d3748;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:16px;">Meeting space summary</h3>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Total meeting space (m²)</label>
            <input type="number" step="0.01" name="total_meeting_space_sqm" value="{{ old('total_meeting_space_sqm', $v?->total_meeting_space_sqm) }}"
                   style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Largest room capacity (guests)</label>
            <input type="number" name="largest_room_capacity" value="{{ old('largest_room_capacity', $v?->largest_room_capacity) }}"
                   style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div><label style="font-weight:600;display:block;margin-bottom:6px;">Starting daily rate</label>
            <div style="display:flex;gap:8px;">
                <select name="currency" style="padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
                    @foreach(['EUR','CHF','USD','GBP'] as $cur)
                        <option value="{{ $cur }}" {{ old('currency', $v?->currency ?? 'EUR') == $cur ? 'selected' : '' }}>{{ $cur }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" name="starting_daily_rate" value="{{ old('starting_daily_rate', $v?->starting_daily_rate) }}"
                       style="flex:1;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
            </div>
        </div>
    </div>
    <div style="margin-top:12px;">
        <label style="font-weight:600;display:block;margin-bottom:6px;">Pricing notes</label>
        <textarea name="pricing_notes" rows="2" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">{{ old('pricing_notes', $v?->pricing_notes) }}</textarea>
    </div>
</div>

<div style="margin-bottom:30px;">
    <h3 style="color:#2d3748;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:16px;">Event types supported</h3>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
        @foreach($eventTypesList as $key => $label)
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
                <input type="checkbox" name="event_types[]" value="{{ $key }}" {{ in_array($key, $selectedEventTypes) ? 'checked' : '' }}>
                {{ $label }}
            </label>
        @endforeach
    </div>
</div>

<div style="margin-bottom:30px;">
    <h3 style="color:#2d3748;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:16px;">Venue amenities</h3>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
        @foreach($venueAmenitiesList as $key => $label)
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
                <input type="checkbox" name="amenities[]" value="{{ $key }}" {{ in_array($key, $selectedAmenities) ? 'checked' : '' }}>
                {{ $label }}
            </label>
        @endforeach
    </div>
</div>

<div style="margin-bottom:30px;">
    <h3 style="color:#2d3748;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:16px;">Photos & video</h3>
    @if($v && is_array($v->images) && count($v->images))
        <div style="display:flex;flex-wrap:gap:12px;margin-bottom:16px;">
            @foreach($v->images as $img)
                <div style="position:relative;">
                    <img src="{{ \App\Services\ImageService::getUrl($img) }}" alt="" style="width:120px;height:80px;object-fit:cover;border-radius:8px;">
                    <label style="display:block;margin-top:4px;font-size:11px;">
                        <input type="checkbox" name="remove_images[]" value="{{ $img }}"> Remove
                    </label>
                </div>
            @endforeach
        </div>
    @endif
    <div style="margin-bottom:12px;">
        <label style="font-weight:600;display:block;margin-bottom:6px;">Upload images</label>
        <input type="file" name="images[]" accept="image/*" multiple style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
    </div>
    <div>
        <label style="font-weight:600;display:block;margin-bottom:6px;">Video URL or upload</label>
        <input type="text" name="video" value="{{ old('video', $v?->video && str_starts_with($v->video, 'http') ? $v->video : '') }}" placeholder="https://..."
               style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;margin-bottom:8px;">
        <input type="file" name="video_file" accept="video/mp4,video/webm" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">
    </div>
</div>

<div style="margin-bottom:30px;">
    <h3 style="color:#2d3748;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:12px;">Meeting rooms / event spaces</h3>
    <p style="color:#718096;font-size:13px;margin-bottom:12px;">
        Add each room with dimensions and setup capacities (theater, classroom, banquet, etc.) — same structure as Cvent venue meeting rooms.
    </p>
    <div id="spaces-container">
        @foreach($oldSpaces as $idx => $space)
            @include('admin.private-venues._space_row', ['idx' => $idx, 'space' => $space, 'setupTypes' => $setupTypes, 'spaceAmenitiesList' => $spaceAmenitiesList])
        @endforeach
    </div>
    <button type="button" id="add-space-btn" style="margin-top:12px;background:#667eea;color:white;padding:10px 16px;border:none;border-radius:8px;cursor:pointer;">
        + Add meeting room
    </button>
</div>

<div style="margin-bottom:24px;">
    <label style="font-weight:600;display:block;margin-bottom:6px;">Internal notes (admin only)</label>
    <textarea name="internal_notes" rows="2" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">{{ old('internal_notes', $v?->internal_notes) }}</textarea>
</div>

<template id="space-row-template">
    @include('admin.private-venues._space_row', ['idx' => '__INDEX__', 'space' => ['setup_capacities'=>[],'amenities'=>[],'is_private'=>true,'status'=>'active'], 'setupTypes' => $setupTypes, 'spaceAmenitiesList' => $spaceAmenitiesList])
</template>

<script>
(function() {
    const container = document.getElementById('spaces-container');
    const tpl = document.getElementById('space-row-template');
    const addBtn = document.getElementById('add-space-btn');

    addBtn.addEventListener('click', function() {
        const index = container.querySelectorAll('.space-row').length;
        const html = tpl.innerHTML.replace(/__INDEX__/g, index);
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        container.appendChild(wrap.firstElementChild);
    });

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-space-btn')) {
            const rows = container.querySelectorAll('.space-row');
            if (rows.length <= 1) {
                alert('At least one meeting room row is required (or leave the name empty to skip).');
                return;
            }
            e.target.closest('.space-row').remove();
        }
    });
})();
</script>
