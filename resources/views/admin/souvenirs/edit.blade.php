@extends('admin.layouts.app')

@section('title', 'Edit Souvenir')
@section('page-title', 'Edit Souvenir')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Edit Souvenir</h2>
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

        <form action="{{ route('admin.souvenirs.update', $souvenir->id) }}" method="POST" enctype="multipart/form-data" style="max-width: 900px; padding: 20px;">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 24px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">Basic Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Name <span style="color: #e53e3e;">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $souvenir->name) }}" required
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('name')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Country</label>
                        <input type="text" name="country" value="{{ old('country', $souvenir->country) }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('country')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">City</label>
                        <input type="text" name="city" value="{{ old('city', $souvenir->city) }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('city')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                    <div></div>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Description</label>
                    <textarea name="description" rows="4" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('description', $souvenir->description) }}</textarea>
                    @error('description')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Price <span style="color: #e53e3e;">*</span></label>
                        <input type="number" name="price" value="{{ old('price', $souvenir->price) }}" step="0.01" min="0" required
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('price')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Currency</label>
                        <input type="text" value="INR" disabled
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background-color:#edf2f7;">
                        <input type="hidden" name="currency" value="INR">
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Min order quantity</label>
                        <input type="number" name="min_order_quantity" value="{{ old('min_order_quantity', $souvenir->min_order_quantity) }}" min="{{ config('souvenir.min_purchase_quantity', 10) }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('min_order_quantity')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Stock</label>
                        <input type="number" name="stock" value="{{ old('stock', $souvenir->stock) }}" min="0"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">Current available quantity.</small>
                        @error('stock')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px;">
                        Location on Map
                    </h3>
                    <div id="souvenir-map-edit"
                         style="width: 100%; height: 320px; border-radius: 8px; border: 2px solid #e2e8f0; margin-bottom: 12px;"></div>
                    <small style="color:#718096; display:block; margin-bottom: 12px;">
                        Click on the map or drag the marker to adjust the location.
                    </small>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Latitude</label>
                            <input id="latitude" type="number" name="latitude" value="{{ old('latitude', $souvenir->latitude) }}" step="0.00000001" min="-90" max="90"
                                   placeholder="e.g. 47.3769"
                                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            @error('latitude')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Longitude</label>
                            <input id="longitude" type="number" name="longitude" value="{{ old('longitude', $souvenir->longitude) }}" step="0.00000001" min="-180" max="180"
                                   placeholder="e.g. 8.5417"
                                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            @error('longitude')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Status <span style="color: #e53e3e;">*</span></label>
                    <select name="status" required style="width: 100%; max-width: 200px; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="active" {{ old('status', $souvenir->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $souvenir->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="pending" {{ old('status', $souvenir->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                    @error('status')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">Images (multiple)</h3>
                @if($souvenir->images && count($souvenir->images) > 0)
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                        @foreach($souvenir->images as $imagePath)
                            <div style="position: relative;">
                                <img src="{{ \App\Services\ImageService::getUrl($imagePath) }}" alt="" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                                <label style="position: absolute; top: 4px; right: 4px; background: #e53e3e; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px; cursor: pointer;">
                                    <input type="checkbox" name="images_to_delete[]" value="{{ $imagePath }}" style="margin-right: 4px;"> Remove
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Add more images</label>
                    <input type="file" name="images[]" accept="image/*" multiple
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">Multiple images allowed. Max 2MB each.</small>
                    @error('images.*')<div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" style="padding: 10px 20px; background: #4299e1; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer;">Update Souvenir</button>
                <a href="{{ route('admin.souvenirs.index') }}" style="padding: 10px 20px; background: #e2e8f0; color: #4a5568; border-radius: 8px; text-decoration: none; font-size: 15px;">Cancel</a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY"></script>
        <script>
            (function () {
                const mapElement = document.getElementById('souvenir-map-edit');
                if (!mapElement) return;

                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');

                const defaultLat = parseFloat(latInput.value) || 30.3165; // Dehradun default
                const defaultLng = parseFloat(lngInput.value) || 78.0322;

                const map = new google.maps.Map(mapElement, {
                    center: { lat: defaultLat, lng: defaultLng },
                    zoom: 8,
                });

                let marker = new google.maps.Marker({
                    position: { lat: defaultLat, lng: defaultLng },
                    map: map,
                    draggable: true,
                });

                function updateInputs(latLng) {
                    latInput.value = latLng.lat().toFixed(8);
                    lngInput.value = latLng.lng().toFixed(8);
                }

                map.addListener('click', function (e) {
                    marker.setPosition(e.latLng);
                    updateInputs(e.latLng);
                });

                marker.addListener('dragend', function (e) {
                    updateInputs(e.latLng);
                });
            })();
        </script>
    @endpush
@endsection
