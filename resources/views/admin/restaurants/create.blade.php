@extends('admin.layouts.app')

@section('title', 'Add New Restaurant')
@section('page-title', 'Add New Restaurant')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Add New Restaurant</h2>
            <a href="{{ route('admin.restaurants.index') }}" 
               style="color: #667eea; text-decoration: none; font-size: 14px;">
                ← Back to Restaurants List
            </a>
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

        <form action="{{ route('admin.restaurants.store') }}" method="POST" enctype="multipart/form-data" style="max-width: 1200px; padding: 20px;">
            @csrf

            <!-- Basic Information Section -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Basic Information</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Restaurant Name <span style="color: #e53e3e;">*</span>
                        </label>
                        <input type="text" 
                               name="restaurant_name" 
                               value="{{ old('restaurant_name') }}" 
                               required
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('restaurant_name')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Description
                    </label>
                    <textarea name="description" 
                              rows="4"
                              style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('description') }}</textarea>
                    @error('description')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Star Rating
                        </label>
                        <select name="star_rating" 
                                style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">Select Rating</option>
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ old('star_rating') == $i ? 'selected' : '' }}>
                                    {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                </option>
                            @endfor
                        </select>
                        @error('star_rating')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Cuisine Type
                        </label>
                        <input type="text" 
                               name="cuisine_type" 
                               value="{{ old('cuisine_type') }}"
                               placeholder="e.g., Indian, Chinese, Italian"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('cuisine_type')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Seating Capacity
                        </label>
                        <input type="number" 
                               name="seating_capacity" 
                               value="{{ old('seating_capacity') }}"
                               min="1"
                               placeholder="Number of seats"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('seating_capacity')
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
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Address Information Section -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Address Information</h3>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Full Address <span style="color: #e53e3e;">*</span>
                    </label>
                    <textarea name="address" 
                              rows="3"
                              required
                              style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('address') }}</textarea>
                    @error('address')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            City
                        </label>
                        <input type="text" 
                               name="city" 
                               value="{{ old('city') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('city')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            State
                        </label>
                        <input type="text" 
                               name="state" 
                               value="{{ old('state') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('state')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Country
                        </label>
                        <input type="text" 
                               name="country" 
                               value="{{ old('country', 'India') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('country')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Pincode
                        </label>
                        <input type="text" 
                               name="pincode" 
                               value="{{ old('pincode') }}"
                               maxlength="10"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('pincode')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Latitude (for Maps)
                        </label>
                        <input type="number" 
                               name="latitude" 
                               value="{{ old('latitude') }}"
                               step="0.00000001"
                               placeholder="e.g., 28.6139"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('latitude')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Longitude (for Maps)
                        </label>
                        <input type="number" 
                               name="longitude" 
                               value="{{ old('longitude') }}"
                               step="0.00000001"
                               placeholder="e.g., 77.2090"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('longitude')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Contact Information</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Phone <span style="color: #e53e3e;">*</span>
                        </label>
                        <input type="text" 
                               name="phone" 
                               value="{{ old('phone') }}" 
                               required
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('phone')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Email
                        </label>
                        <input type="email" 
                               name="email" 
                               value="{{ old('email') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('email')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Alternate Phone
                        </label>
                        <input type="text" 
                               name="alternate_phone" 
                               value="{{ old('alternate_phone') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('alternate_phone')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Website
                        </label>
                        <input type="url" 
                               name="website" 
                               value="{{ old('website') }}"
                               placeholder="https://example.com"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('website')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Images Section -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Images</h3>
                
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Restaurant Images (Multiple)
                    </label>
                    <input type="file" 
                           name="images[]" 
                           accept="image/*"
                           multiple
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">You can select multiple images. Max size: 2MB each. Allowed: JPEG, PNG, GIF, WEBP</small>
                    @error('images.*')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Restaurant Video Section -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Restaurant Video</h3>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Upload video file
                    </label>
                    <input type="file"
                           name="video"
                           accept="video/mp4,video/quicktime,video/webm"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">MP4, MOV or WEBM. Max 50MB.</small>
                    @error('video')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Or paste video URL
                    </label>
                    <input type="url"
                           name="video_url"
                           value="{{ old('video_url') }}"
                           placeholder="https://www.youtube.com/embed/... or https://vimeo.com/... or direct video URL"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">YouTube embed URL, Vimeo URL, or direct link to a video file.</small>
                    @error('video_url')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Business Information Section -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Business Information</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Tax Number
                        </label>
                        <input type="text"
                               name="tax_number"
                               value="{{ old('tax_number') }}"
                               maxlength="15"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; text-transform: uppercase;">
                        @error('tax_number')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            License Number
                        </label>
                        <input type="text" 
                               name="license_number" 
                               value="{{ old('license_number') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('license_number')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Features & Amenities</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #2d3748; cursor: pointer;">
                            <input type="checkbox" 
                                   name="parking_available" 
                                   value="1"
                                   {{ old('parking_available') ? 'checked' : '' }}
                                   style="width: 18px; height: 18px; cursor: pointer;">
                            Parking Available
                        </label>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #2d3748; cursor: pointer;">
                            <input type="checkbox" 
                                   name="wifi_available" 
                                   value="1"
                                   {{ old('wifi_available') ? 'checked' : '' }}
                                   style="width: 18px; height: 18px; cursor: pointer;">
                            WiFi Available
                        </label>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #2d3748; cursor: pointer;">
                            <input type="checkbox" 
                                   name="accepts_reservations" 
                                   value="1"
                                   {{ old('accepts_reservations') ? 'checked' : '' }}
                                   style="width: 18px; height: 18px; cursor: pointer;">
                            Accepts Reservations
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Amenities (comma-separated)
                    </label>
                    <input type="text" 
                           name="amenities_input" 
                           value="{{ old('amenities_input') }}"
                           placeholder="e.g., AC, Live Music, Bar, Outdoor Seating, Valet Parking"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">Enter amenities separated by commas</small>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Payment Methods (comma-separated)
                    </label>
                    <input type="text" 
                           name="payment_methods_input" 
                           value="{{ old('payment_methods_input') }}"
                           placeholder="e.g., Cash, Card, UPI, Net Banking, Digital Wallets"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">Enter payment methods separated by commas</small>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Opening Hours (JSON format or leave blank)
                    </label>
                    <textarea name="opening_hours_input" 
                              rows="4"
                              placeholder='{"monday": "09:00-22:00", "tuesday": "09:00-22:00", "wednesday": "09:00-22:00", "thursday": "09:00-22:00", "friday": "09:00-23:00", "saturday": "09:00-23:00", "sunday": "10:00-22:00"}'
                              style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: monospace; resize: vertical;">{{ old('opening_hours_input') }}</textarea>
                    <small style="color: #718096; font-size: 12px; display: block; margin-top: 4px;">Enter opening hours in JSON format for each day</small>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="submit" 
                        style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    Create Restaurant
                </button>
                <a href="{{ route('admin.restaurants.index') }}" 
                   style="background: #e2e8f0; color: #2d3748; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-block;">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        // Convert comma-separated amenities to array on form submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const amenitiesInput = document.querySelector('input[name="amenities_input"]');
            const paymentMethodsInput = document.querySelector('input[name="payment_methods_input"]');
            const openingHoursInput = document.querySelector('textarea[name="opening_hours_input"]');
            
            // Convert amenities to array
            if (amenitiesInput && amenitiesInput.value) {
                const amenities = amenitiesInput.value.split(',').map(item => item.trim()).filter(item => item);
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'amenities';
                hiddenInput.value = JSON.stringify(amenities);
                this.appendChild(hiddenInput);
            }
            
            // Convert payment methods to array
            if (paymentMethodsInput && paymentMethodsInput.value) {
                const paymentMethods = paymentMethodsInput.value.split(',').map(item => item.trim()).filter(item => item);
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'payment_methods';
                hiddenInput.value = JSON.stringify(paymentMethods);
                this.appendChild(hiddenInput);
            }
            
            // Parse opening hours JSON
            if (openingHoursInput && openingHoursInput.value.trim()) {
                try {
                    const openingHours = JSON.parse(openingHoursInput.value);
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'opening_hours';
                    hiddenInput.value = JSON.stringify(openingHours);
                    this.appendChild(hiddenInput);
                } catch (e) {
                    alert('Invalid JSON format for opening hours. Please check the format.');
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
@endsection

