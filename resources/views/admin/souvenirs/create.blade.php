@extends('admin.layouts.app')

@section('title', 'Add Souvenir')
@section('page-title', 'Add Souvenir')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Add New Souvenir</h2>
            <a href="{{ route('admin.souvenirs.index') }}" style="color: #667eea; text-decoration: none; font-size: 14px;">← Back to List</a>
        </div>

        @if($errors->any())
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin: 20px; border: 1px solid #f5c6cb;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 8px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.souvenirs.store') }}" method="POST" enctype="multipart/form-data" style="max-width: 900px; padding: 20px;">
            @csrf

            <div style="margin-bottom: 24px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">Basic Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Name <span style="color: #e53e3e;">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('name')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Country</label>
                        <input type="text" name="country" value="{{ old('country') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('country')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">City</label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('city')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                    <div></div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Description</label>
                    <textarea name="description" rows="4"
                              style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('description') }}</textarea>
                    @error('description')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Price <span style="color: #e53e3e;">*</span></label>
                        <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0" required
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('price')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Currency</label>
                        <select name="currency" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="EUR" {{ old('currency', 'EUR') === 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="CHF" {{ old('currency') === 'CHF' ? 'selected' : '' }}>CHF</option>
                            <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP</option>
                            <option value="INR" {{ old('currency') === 'INR' ? 'selected' : '' }}>INR</option>
                        </select>
                        @error('currency')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Min Order Quantity</label>
                        <input type="number" name="min_order_quantity" value="{{ old('min_order_quantity', config('souvenir.min_purchase_quantity', 10)) }}"
                               min="{{ config('souvenir.min_purchase_quantity', 10) }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('min_order_quantity')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Stock</label>
                        <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">Current available quantity.</small>
                        @error('stock')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px;">Location on Map</h3>
                    <div id="souvenir-map-create"
                         style="width: 100%; height: 320px; border-radius: 8px; border: 2px solid #e2e8f0; margin-bottom: 12px;"></div>
                    <small style="color:#718096; display:block; margin-bottom: 12px;">
                        Click on the map or drag the marker to set the location.
                    </small>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Latitude</label>
                            <input id="latitude" type="number" name="latitude" value="{{ old('latitude') }}" step="0.00000001" min="-90" max="90"
                                   placeholder="e.g. 47.3769"
                                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            @error('latitude')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Longitude</label>
                            <input id="longitude" type="number" name="longitude" value="{{ old('longitude') }}" step="0.00000001" min="-180" max="180"
                                   placeholder="e.g. 8.5417"
                                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            @error('longitude')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Status <span style="color: #e53e3e;">*</span></label>
                    <select name="status" required style="width: 100%; max-width: 200px; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="active"   {{ old('status') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="pending"  {{ old('status', 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                    @error('status')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">Images (multiple)</h3>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Upload images</label>
                    <input type="file" name="images[]" accept="image/*" multiple
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">Multiple images allowed. Max 2MB each.</small>
                    @error('images.*')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" style="padding: 10px 20px; background: #48bb78; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer;">Create Souvenir</button>
                <a href="{{ route('admin.souvenirs.index') }}" style="padding: 10px 20px; background: #e2e8f0; color: #4a5568; border-radius: 8px; text-decoration: none; font-size: 15px;">Cancel</a>
            </div>
        </form>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            (function () {
                const mapElement = document.getElementById('souvenir-map-create');
                if (!mapElement) return;

                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');

                const defaultLat = parseFloat(latInput.value) || 30.3165;
                const defaultLng = parseFloat(lngInput.value) || 78.0322;

                const map = L.map(mapElement).setView([defaultLat, defaultLng], 6);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                let marker = null;

                function placeMarker(lat, lng) {
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                        marker.on('dragend', function () {
                            const pos = marker.getLatLng();
                            latInput.value = pos.lat.toFixed(8);
                            lngInput.value = pos.lng.toFixed(8);
                        });
                    }
                    latInput.value = lat.toFixed(8);
                    lngInput.value = lng.toFixed(8);
                }

                // If pre-filled (old input on validation fail), place marker immediately
                if (parseFloat(latInput.value) && parseFloat(lngInput.value)) {
                    placeMarker(parseFloat(latInput.value), parseFloat(lngInput.value));
                }

                map.on('click', function (e) {
                    placeMarker(e.latlng.lat, e.latlng.lng);
                });

                // Sync map when inputs change manually
                [latInput, lngInput].forEach(function (input) {
                    input.addEventListener('change', function () {
                        const lat = parseFloat(latInput.value);
                        const lng = parseFloat(lngInput.value);
                        if (!isNaN(lat) && !isNaN(lng)) {
                            placeMarker(lat, lng);
                            map.setView([lat, lng]);
                        }
                    });
                });
            })();
        </script>
    @endpush
@endsection
