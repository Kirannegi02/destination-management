@php
    $caps = $space['setup_capacities'] ?? [];
    $spaceAmenities = $space['amenities'] ?? [];
@endphp
<div class="space-row" style="border:2px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:16px;background:#fafbfc;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <strong style="color:#2d3748;">Meeting room #{{ is_numeric($idx) ? ((int)$idx + 1) : '' }}</strong>
        <button type="button" class="remove-space-btn" style="background:#e53e3e;color:white;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;">Remove</button>
    </div>
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-bottom:12px;">
        <div>
            <label style="font-weight:600;font-size:13px;">Room name <span style="color:#e53e3e;">*</span></label>
            <input type="text" name="spaces[{{ $idx }}][name]" value="{{ $space['name'] ?? '' }}"
                   style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;">
        </div>
        <div>
            <label style="font-weight:600;font-size:13px;">Display order</label>
            <input type="number" name="spaces[{{ $idx }}][sort_order]" value="{{ $space['sort_order'] ?? '' }}"
                   style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;">
        </div>
    </div>
    <div style="margin-bottom:12px;">
        <label style="font-weight:600;font-size:13px;">Description</label>
        <textarea name="spaces[{{ $idx }}][description]" rows="2" style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;">{{ $space['description'] ?? '' }}</textarea>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px;">
        <div>
            <label style="font-size:12px;font-weight:600;">Total space (m²)</label>
            <input type="number" step="0.01" name="spaces[{{ $idx }}][total_space_sqm]" value="{{ $space['total_space_sqm'] ?? '' }}"
                   style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;">Length (m)</label>
            <input type="number" step="0.01" name="spaces[{{ $idx }}][length_m]" value="{{ $space['length_m'] ?? '' }}"
                   style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;">Width (m)</label>
            <input type="number" step="0.01" name="spaces[{{ $idx }}][width_m]" value="{{ $space['width_m'] ?? '' }}"
                   style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;">Ceiling height (m)</label>
            <input type="number" step="0.01" name="spaces[{{ $idx }}][ceiling_height_m]" value="{{ $space['ceiling_height_m'] ?? '' }}"
                   style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;">
        </div>
    </div>
    <div style="margin-bottom:12px;">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:8px;">Setup capacities (max guests per layout)</label>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
            @foreach($setupTypes as $setupKey => $setupLabel)
                <div>
                    <label style="font-size:11px;color:#4a5568;">{{ $setupLabel }}</label>
                    <input type="number" min="0" name="spaces[{{ $idx }}][setup_capacities][{{ $setupKey }}]"
                           value="{{ $caps[$setupKey] ?? '' }}"
                           style="width:100%;padding:6px;border:1px solid #e2e8f0;border-radius:4px;">
                </div>
            @endforeach
        </div>
    </div>
    <div style="margin-bottom:12px;">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:8px;">Room amenities / equipment</label>
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            @foreach($spaceAmenitiesList as $aKey => $aLabel)
                <label style="font-size:12px;display:flex;align-items:center;gap:4px;">
                    <input type="checkbox" name="spaces[{{ $idx }}][amenities][]" value="{{ $aKey }}"
                        {{ in_array($aKey, $spaceAmenities) ? 'checked' : '' }}>
                    {{ $aLabel }}
                </label>
            @endforeach
        </div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">
        <label style="font-size:13px;"><input type="checkbox" name="spaces[{{ $idx }}][is_outdoor]" value="1" {{ !empty($space['is_outdoor']) ? 'checked' : '' }}> Outdoor space</label>
        <label style="font-size:13px;"><input type="checkbox" name="spaces[{{ $idx }}][is_private]" value="1" {{ ($space['is_private'] ?? true) ? 'checked' : '' }}> Private</label>
        <label style="font-size:13px;"><input type="checkbox" name="spaces[{{ $idx }}][is_semi_private]" value="1" {{ !empty($space['is_semi_private']) ? 'checked' : '' }}> Semi-private</label>
        <label style="font-size:13px;"><input type="checkbox" name="spaces[{{ $idx }}][wheelchair_accessible]" value="1" {{ !empty($space['wheelchair_accessible']) ? 'checked' : '' }}> Wheelchair accessible</label>
        <select name="spaces[{{ $idx }}][status]" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;margin-left:auto;">
            <option value="active" {{ ($space['status'] ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ ($space['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
</div>
