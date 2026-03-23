@extends('admin.layouts.app')

@section('title', 'Guide Details')
@section('page-title', 'Guide Details')

@section('content')
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2 class="card-title">{{ $guide->full_name ?? $guide->title }}</h2>
                <div style="color:#4a5568; font-size:13px;">ID #{{ $guide->id }} • {{ ucfirst($guide->status) }}</div>
            </div>
            <a href="{{ route('admin.guides.index') }}" style="color:#667eea; text-decoration:none; font-size:14px;">← Back to Guides List</a>
        </div>

        <div style="padding:20px; display:grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap:20px;">
            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Profile</h4>
                <p style="color:#4a5568;">Name: <strong>{{ $guide->full_name ?? 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Gender: <strong>{{ ucfirst($guide->gender ?? 'n/a') }}</strong></p>
                <p style="color:#4a5568;">DOB: <strong>{{ $guide->date_of_birth ? $guide->date_of_birth->format('d M Y') : 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Experience: <strong>{{ $guide->years_experience ?? 0 }} yrs</strong></p>
                <p style="color:#4a5568;">Nationality: <strong>{{ $guide->nationality ?? 'N/A' }}</strong></p>
            </div>

            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Contact</h4>
                <p style="color:#4a5568;">Phone: <strong>{{ $guide->phone_country_code }} {{ $guide->phone_number }}</strong></p>
                <p style="color:#4a5568;">WhatsApp: <strong>{{ $guide->whatsapp_number ?: 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Email: <strong>{{ $guide->email ?: 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Emergency: <strong>{{ $guide->emergency_contact_number ?: 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Verification: <strong>{{ ucfirst($guide->verification_status) }}</strong></p>
            </div>

            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Location & Language</h4>
                <p style="color:#4a5568;">City / Country: <strong>{{ $guide->city ?? 'N/A' }}, {{ $guide->country ?? 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Operating Areas: <strong>{{ implode(', ', $guide->operating_areas ?? []) ?: 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Languages: <strong>{{ $guide->primary_language ?? 'N/A' }}</strong> | Others: <strong>{{ implode(', ', $guide->other_languages ?? []) }}</strong></p>
                <p style="color:#4a5568;">Proficiency: <strong>{{ ucfirst($guide->language_proficiency ?? 'N/A') }}</strong></p>
            </div>
        </div>

        <div style="padding:20px; display:grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap:20px;">
            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Availability</h4>
                <p style="color:#4a5568;">Date Window: <strong>{{ $guide->available_from_date ? $guide->available_from_date->format('d M Y') : '—' }} - {{ $guide->available_to_date ? $guide->available_to_date->format('d M Y') : '—' }}</strong></p>
                <p style="color:#4a5568;">Daily: <strong>{{ $guide->daily_start_time ? $guide->daily_start_time->format('H:i') : '—' }} - {{ $guide->daily_end_time ? $guide->daily_end_time->format('H:i') : '—' }}</strong></p>
                <p style="color:#4a5568;">Available Days: <strong>{{ implode(', ', $guide->available_days ?? []) ?: 'All' }}</strong></p>
                <p style="color:#4a5568;">Blackout: <strong>{{ implode(', ', $guide->blackout_dates ?? []) ?: 'None' }}</strong></p>
            </div>

            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Booking Flow</h4>
                <p style="color:#4a5568;">Start / End: <strong>{{ $guide->default_start_location ?? 'N/A' }} → {{ $guide->default_end_location ?? 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Meeting / Drop: <strong>{{ $guide->start_point ?? 'N/A' }} → {{ $guide->end_point ?? 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Slots: <strong>{{ implode(', ', $guide->start_time_slots ?? []) ?: 'Flexible' }}</strong></p>
                <p style="color:#4a5568;">Max / Day: <strong>{{ $guide->max_bookings_per_day ?? 'Not set' }}</strong></p>
            </div>

            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Pricing</h4>
                <p style="color:#4a5568;">Half Day: <strong>{{ $guide->half_day_price ? '₹'.number_format($guide->half_day_price,2) : 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Full Day: <strong>{{ $guide->full_day_price ? '₹'.number_format($guide->full_day_price,2) : 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Extra Hour: <strong>{{ $guide->extra_hour_price ? '₹'.number_format($guide->extra_hour_price,2) : 'N/A' }}</strong></p>
            </div>
        </div>

        <div style="padding:20px;">
            <h4 style="color:#2d3748; margin-bottom:10px;">Packages</h4>
            @if($guide->packages->count())
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:12px;">
                    @foreach($guide->packages as $package)
                        <div style="border:1px solid #e2e8f0; border-radius:10px; padding:12px; background:#f9fafb;">
                            <strong>{{ $package->service_name ?? 'Package' }}</strong>
                            <div style="color:#4a5568; font-size:13px; margin-top:6px;">
                                <div>Type: {{ strtoupper($package->service_type) ?: 'N/A' }} • {{ $package->duration_hours ?? '—' }} hrs</div>
                                <div>Includes: {{ $package->includes_lunch ? 'Lunch ' : '' }}{{ $package->includes_dinner ? 'Dinner' : '' }}</div>
                                <div>Status: {{ $package->active ? 'Active' : 'Inactive' }}</div>
                            </div>
                            @if($package->description)
                                <div style="margin-top:8px; color:#4a5568; font-size:13px;">{{ $package->description }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color:#4a5568;">No packages added.</p>
            @endif
        </div>

        <div style="padding:20px;">
            <h4 style="color:#2d3748; margin-bottom:10px;">Descriptions & Notes</h4>
            <p style="color:#4a5568; line-height:1.6;">{{ $guide->description ?: 'No description provided.' }}</p>
            @if($guide->notes)
                <h4 style="color:#2d3748; margin:16px 0 8px;">Notes</h4>
                <p style="color:#4a5568; line-height:1.6;">{{ $guide->notes }}</p>
            @endif
            @if($guide->indian_special_notes)
                <h4 style="color:#2d3748; margin:16px 0 8px;">Indian Traveler Notes</h4>
                <p style="color:#4a5568; line-height:1.6;">{{ $guide->indian_special_notes }}</p>
            @endif
        </div>
    </div>
@endsection
