@extends('admin.layouts.app')

@section('title', 'Restaurant Details')
@section('page-title', 'Restaurant Details')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ $restaurant->restaurant_name }}</h2>
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="{{ route('admin.restaurants.edit', $restaurant->id) }}" 
                   style="padding: 8px 16px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500;">
                    ✏️ Edit Restaurant
                </a>
                <a href="{{ route('admin.restaurants.index') }}" 
                   style="color: #667eea; text-decoration: none; font-size: 14px;">
                    ← Back to List
                </a>
            </div>
        </div>

        <div style="padding: 20px;">
            <!-- Status Badge -->
            <div style="margin-bottom: 24px;">
                @if($restaurant->status === 'active')
                    <span class="badge badge-success" style="font-size: 14px; padding: 8px 16px;">✓ Active</span>
                @elseif($restaurant->status === 'inactive')
                    <span class="badge badge-danger" style="font-size: 14px; padding: 8px 16px;">✗ Inactive</span>
                @else
                    <span class="badge" style="background: #fbbf24; color: white; font-size: 14px; padding: 8px 16px;">⏳ Pending</span>
                @endif
            </div>

            <!-- Images Gallery -->
            @if($restaurant->images && count($restaurant->images) > 0)
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">Restaurant Images</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
                        @foreach($restaurant->images as $imagePath)
                            <img src="{{ \App\Services\ImageService::getUrl($imagePath) }}" 
                                 alt="Restaurant Image" 
                                 style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Restaurant Video -->
            @if($restaurant->video_url)
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">Restaurant Video</h3>
                    @php
                        $videoUrl = $restaurant->video_url;
                        $isYoutube = preg_match('%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $videoUrl, $yt) || preg_match('%youtube\.com/embed/([^"&?/\s]+)%i', $videoUrl, $yt);
                        $isVimeo = preg_match('%vimeo\.com/(?:video/)?(\d+)%i', $videoUrl, $vm);
                    @endphp
                    @if($isYoutube)
                        @php $ytId = $yt[1] ?? null; @endphp
                        @if($ytId)
                            <div style="max-width: 800px; aspect-ratio: 16/9; border-radius: 8px; overflow: hidden; border: 2px solid #e2e8f0;">
                                <iframe src="https://www.youtube.com/embed/{{ $ytId }}?rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%;height:100%;border:none;"></iframe>
                            </div>
                        @endif
                    @elseif($isVimeo)
                        @php $vmId = $vm[1] ?? null; @endphp
                        @if($vmId)
                            <div style="max-width: 800px; aspect-ratio: 16/9; border-radius: 8px; overflow: hidden; border: 2px solid #e2e8f0;">
                                <iframe src="https://player.vimeo.com/video/{{ $vmId }}" allow="fullscreen; picture-in-picture" allowfullscreen style="width:100%;height:100%;border:none;"></iframe>
                            </div>
                        @endif
                    @else
                        <div style="max-width: 800px; border-radius: 8px; overflow: hidden; border: 2px solid #e2e8f0;">
                            <video controls playsinline style="width:100%;display:block;" preload="metadata">
                                <source src="{{ $videoUrl }}" type="video/mp4">
                                <p style="padding:16px;">Your browser does not support the video tag. <a href="{{ $videoUrl }}" target="_blank" rel="noopener">Open video</a>.</p>
                            </video>
                        </div>
                    @endif
                </div>
            @endif

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <!-- Left Column - Main Information -->
                <div>
                    <!-- Basic Information -->
                    <div style="margin-bottom: 30px; padding: 20px; background: #f7fafc; border-radius: 8px;">
                        <h3 style="color: #2d3748; margin-bottom: 16px; font-size: 18px;">Basic Information</h3>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <strong style="color: #4a5568; display: block; margin-bottom: 4px;">Restaurant Name:</strong>
                                <span style="color: #2d3748; font-size: 16px;">{{ $restaurant->restaurant_name }}</span>
                            </div>
                            @if($restaurant->description)
                                <div>
                                    <strong style="color: #4a5568; display: block; margin-bottom: 4px;">Description:</strong>
                                    <p style="color: #2d3748; line-height: 1.6;">{{ $restaurant->description }}</p>
                                </div>
                            @endif
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 8px;">
                                @if($restaurant->star_rating)
                                    <div>
                                        <strong style="color: #4a5568; display: block; margin-bottom: 4px; font-size: 12px;">Star Rating:</strong>
                                        <span style="color: #fbbf24; font-size: 18px;">⭐ {{ $restaurant->star_rating }}/5</span>
                                    </div>
                                @endif
                                @if($restaurant->price)
                                    <div>
                                        <strong style="color: #4a5568; display: block; margin-bottom: 4px; font-size: 12px;">Price:</strong>
                                        <span style="color: #667eea; font-weight: 600;">{{ $restaurant->price_formatted }}</span>
                                    </div>
                                @endif
                                @if($restaurant->cuisine_type)
                                    <div>
                                        <strong style="color: #4a5568; display: block; margin-bottom: 4px; font-size: 12px;">Cuisine Type:</strong>
                                        <span style="color: #2d3748;">{{ $restaurant->cuisine_type }}</span>
                                    </div>
                                @endif
                            </div>
                            @if($restaurant->seating_capacity)
                                <div style="margin-top: 8px;">
                                    <strong style="color: #4a5568; display: block; margin-bottom: 4px;">Seating Capacity:</strong>
                                    <span style="color: #2d3748; font-size: 16px;">👥 {{ $restaurant->seating_capacity }} seats</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div style="margin-bottom: 30px; padding: 20px; background: #f7fafc; border-radius: 8px;">
                        <h3 style="color: #2d3748; margin-bottom: 16px; font-size: 18px;">📍 Address Information</h3>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <strong style="color: #4a5568; display: block; margin-bottom: 4px;">Full Address:</strong>
                                <p style="color: #2d3748; line-height: 1.6;">{{ $restaurant->address }}</p>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px;">
                                @if($restaurant->city)
                                    <div>
                                        <strong style="color: #4a5568; display: block; margin-bottom: 4px; font-size: 12px;">City:</strong>
                                        <span style="color: #2d3748;">{{ $restaurant->city }}</span>
                                    </div>
                                @endif
                                @if($restaurant->state)
                                    <div>
                                        <strong style="color: #4a5568; display: block; margin-bottom: 4px; font-size: 12px;">State:</strong>
                                        <span style="color: #2d3748;">{{ $restaurant->state }}</span>
                                    </div>
                                @endif
                                @if($restaurant->country)
                                    <div>
                                        <strong style="color: #4a5568; display: block; margin-bottom: 4px; font-size: 12px;">Country:</strong>
                                        <span style="color: #2d3748;">{{ $restaurant->country }}</span>
                                    </div>
                                @endif
                                @if($restaurant->pincode)
                                    <div>
                                        <strong style="color: #4a5568; display: block; margin-bottom: 4px; font-size: 12px;">Pincode:</strong>
                                        <span style="color: #2d3748;">{{ $restaurant->pincode }}</span>
                                    </div>
                                @endif
                            </div>
                            @if($restaurant->latitude && $restaurant->longitude)
                                <div style="margin-top: 8px;">
                                    <strong style="color: #4a5568; display: block; margin-bottom: 4px;">Location Coordinates:</strong>
                                    <span style="color: #2d3748; font-family: monospace;">
                                        Lat: {{ $restaurant->latitude }}, Long: {{ $restaurant->longitude }}
                                    </span>
                                    <br>
                                    <a href="https://www.google.com/maps?q={{ $restaurant->latitude }},{{ $restaurant->longitude }}" 
                                       target="_blank"
                                       style="color: #667eea; text-decoration: none; font-size: 14px; margin-top: 4px; display: inline-block;">
                                        🗺️ View on Google Maps
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div style="margin-bottom: 30px; padding: 20px; background: #f7fafc; border-radius: 8px;">
                        <h3 style="color: #2d3748; margin-bottom: 16px; font-size: 18px;">📞 Contact Information</h3>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <strong style="color: #4a5568; display: block; margin-bottom: 4px;">Phone:</strong>
                                <a href="tel:{{ $restaurant->phone }}" style="color: #667eea; text-decoration: none; font-size: 16px;">
                                    📞 {{ $restaurant->phone }}
                                </a>
                            </div>
                            @if($restaurant->email)
                                <div>
                                    <strong style="color: #4a5568; display: block; margin-bottom: 4px;">Email:</strong>
                                    <a href="mailto:{{ $restaurant->email }}" style="color: #667eea; text-decoration: none;">
                                        ✉️ {{ $restaurant->email }}
                                    </a>
                                </div>
                            @endif
                            @if($restaurant->alternate_phone)
                                <div>
                                    <strong style="color: #4a5568; display: block; margin-bottom: 4px;">Alternate Phone:</strong>
                                    <a href="tel:{{ $restaurant->alternate_phone }}" style="color: #667eea; text-decoration: none;">
                                        📱 {{ $restaurant->alternate_phone }}
                                    </a>
                                </div>
                            @endif
                            @if($restaurant->website)
                                <div>
                                    <strong style="color: #4a5568; display: block; margin-bottom: 4px;">Website:</strong>
                                    <a href="{{ $restaurant->website }}" target="_blank" style="color: #667eea; text-decoration: none;">
                                        🌐 {{ $restaurant->website }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Opening Hours -->
                    @if($restaurant->opening_hours && count($restaurant->opening_hours) > 0)
                        <div style="margin-bottom: 30px; padding: 20px; background: #f7fafc; border-radius: 8px;">
                            <h3 style="color: #2d3748; margin-bottom: 16px; font-size: 18px;">🕐 Opening Hours</h3>
                            <div style="display: grid; gap: 8px;">
                                @foreach($restaurant->opening_hours as $day => $hours)
                                    <div style="display: flex; justify-content: space-between; padding: 8px; background: white; border-radius: 4px;">
                                        <strong style="color: #4a5568; text-transform: capitalize;">{{ $day }}:</strong>
                                        <span style="color: #2d3748;">{{ $hours }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Column - Additional Details -->
                <div>
                    <!-- Features & Amenities -->
                    <div style="margin-bottom: 30px; padding: 20px; background: #f7fafc; border-radius: 8px;">
                        <h3 style="color: #2d3748; margin-bottom: 16px; font-size: 18px;">✨ Features</h3>
                        <div style="display: grid; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if($restaurant->parking_available)
                                    <span style="color: #48bb78; font-size: 18px;">✓</span>
                                @else
                                    <span style="color: #cbd5e0; font-size: 18px;">✗</span>
                                @endif
                                <span style="color: #2d3748;">Parking Available</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if($restaurant->wifi_available)
                                    <span style="color: #48bb78; font-size: 18px;">✓</span>
                                @else
                                    <span style="color: #cbd5e0; font-size: 18px;">✗</span>
                                @endif
                                <span style="color: #2d3748;">WiFi Available</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if($restaurant->accepts_reservations)
                                    <span style="color: #48bb78; font-size: 18px;">✓</span>
                                @else
                                    <span style="color: #cbd5e0; font-size: 18px;">✗</span>
                                @endif
                                <span style="color: #2d3748;">Accepts Reservations</span>
                            </div>
                        </div>
                    </div>

                    <!-- Amenities -->
                    @if($restaurant->amenities && count($restaurant->amenities) > 0)
                        <div style="margin-bottom: 30px; padding: 20px; background: #f7fafc; border-radius: 8px;">
                            <h3 style="color: #2d3748; margin-bottom: 16px; font-size: 18px;">🏆 Amenities</h3>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                @foreach($restaurant->amenities as $amenity)
                                    <span style="background: white; padding: 6px 12px; border-radius: 6px; font-size: 13px; color: #2d3748; border: 1px solid #e2e8f0;">
                                        {{ $amenity }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Payment Methods -->
                    @if($restaurant->payment_methods && count($restaurant->payment_methods) > 0)
                        <div style="margin-bottom: 30px; padding: 20px; background: #f7fafc; border-radius: 8px;">
                            <h3 style="color: #2d3748; margin-bottom: 16px; font-size: 18px;">💳 Payment Methods</h3>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                @foreach($restaurant->payment_methods as $method)
                                    <span style="background: white; padding: 6px 12px; border-radius: 6px; font-size: 13px; color: #2d3748; border: 1px solid #e2e8f0;">
                                        {{ $method }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Business Information -->
                    <div style="margin-bottom: 30px; padding: 20px; background: #f7fafc; border-radius: 8px;">
                        <h3 style="color: #2d3748; margin-bottom: 16px; font-size: 18px;">🏢 Business Information</h3>
                        <div style="display: grid; gap: 12px;">
                            @if($restaurant->tax_number)
                                <div>
                                    <strong style="color: #4a5568; display: block; margin-bottom: 4px; font-size: 12px;">Tax Number:</strong>
                                    <span style="color: #2d3748; font-family: monospace;">{{ $restaurant->tax_number }}</span>
                                </div>
                            @endif
                            @if($restaurant->license_number)
                                <div>
                                    <strong style="color: #4a5568; display: block; margin-bottom: 4px; font-size: 12px;">License Number:</strong>
                                    <span style="color: #2d3748;">{{ $restaurant->license_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div style="padding: 20px; background: #f7fafc; border-radius: 8px;">
                        <h3 style="color: #2d3748; margin-bottom: 16px; font-size: 18px;">📅 Record Information</h3>
                        <div style="display: grid; gap: 8px; font-size: 13px;">
                            <div>
                                <strong style="color: #4a5568;">Created:</strong>
                                <span style="color: #2d3748;">{{ $restaurant->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div>
                                <strong style="color: #4a5568;">Last Updated:</strong>
                                <span style="color: #2d3748;">{{ $restaurant->updated_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

