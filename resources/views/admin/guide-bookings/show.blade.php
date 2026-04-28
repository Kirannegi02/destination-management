@extends('admin.layouts.app')

@section('title', 'Guide Booking #' . $booking->id)
@section('page-title', 'Guide Booking #' . $booking->id)

@section('content')
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
            <div>
                <h2 class="card-title" style="margin-bottom:4px;">Guide Booking #{{ $booking->id }}</h2>
                <p style="color:#718096; font-size:14px;">
                    {{ $booking->service_date?->format('d M Y') ?? 'N/A' }}
                    @if($booking->start_time)
                        | {{ $booking->start_time?->format('H:i') }}
                    @elseif($booking->start_time_slot)
                        | {{ $booking->start_time_slot }}
                    @endif
                    | Status: <strong>{{ ucfirst($booking->status) }}</strong>
                </p>
            </div>
            <a href="{{ route('admin.guide_bookings.index') }}" style="color:#667eea; text-decoration:none;">← Back to list</a>
        </div>

        @if(session('success'))
            <div style="margin:16px; padding:12px; background:#c6f6d5; color:#22543d; border-radius:6px;">
                {{ session('success') }}
            </div>
        @endif

        <div style="padding:20px; display:grid; grid-template-columns:1fr 1fr; gap:24px;">
            <div style="background:#f7fafc; padding:16px; border-radius:8px;">
                <h3 style="margin-bottom:12px; font-size:16px; color:#2d3748;">Guest / contact</h3>
                <p><strong>User</strong>
                    @if($booking->user)
                        <a href="{{ route('admin.users.edit', $booking->user_id) }}" style="color:#2b6cb0; text-decoration:none;">
                            {{ $booking->user->name }}
                        </a>
                    @else
                        —
                    @endif
                </p>
                <p style="color:#4a5568;">
                    <strong>Contact name</strong> {{ $booking->contact_name ?? '—' }}
                </p>
                <p style="color:#4a5568;">
                    <strong>Contact email</strong> {{ $booking->contact_email ?? '—' }}
                </p>
                <p style="color:#4a5568;">
                    <strong>Contact phone</strong> {{ $booking->contact_phone ?? '—' }}
                </p>
                <p style="color:#718096; font-size:13px; margin-top:12px;">
                    <strong>Created at</strong> {{ $booking->created_at?->format('d M Y, H:i') }}
                </p>
            </div>

            <div style="background:#f7fafc; padding:16px; border-radius:8px;">
                <h3 style="margin-bottom:12px; font-size:16px; color:#2d3748;">Guide & service</h3>
                <p>
                    <strong>Guide</strong>
                    @if($booking->guide)
                        <a href="{{ route('admin.guides.show', $booking->guide_id) }}" style="color:#2b6cb0; text-decoration:none; font-weight:600;">
                            {{ $booking->guide->full_name ?? $booking->guide->title }}
                        </a>
                    @else
                        —
                    @endif
                </p>
                <p style="color:#4a5568;">
                    <strong>Package</strong>
                    @if($booking->package)
                        {{ $booking->package->service_name ?? $booking->package->service_type }}
                    @else
                        —
                    @endif
                </p>
                <p style="color:#4a5568;">
                    <strong>Service date</strong> {{ $booking->service_date?->format('Y-m-d') ?? 'N/A' }}
                </p>
                <p style="color:#4a5568;">
                    <strong>Time</strong>
                    @if($booking->start_time)
                        {{ $booking->start_time?->format('H:i') }}
                    @elseif($booking->start_time_slot)
                        {{ $booking->start_time_slot }}
                    @else
                        N/A
                    @endif
                </p>
                <p style="color:#4a5568;">
                    <strong>Duration (hours)</strong> {{ $booking->duration_hours ?? '—' }}
                </p>
                <p style="color:#4a5568;">
                    <strong>Start location</strong> {{ $booking->start_location ?? '—' }}
                </p>
                <p style="color:#4a5568;">
                    <strong>End location</strong> {{ $booking->end_location ?? '—' }}
                </p>
                <p style="color:#4a5568;">
                    <strong>Start time slot</strong> {{ $booking->start_time_slot ?? '—' }}
                </p>
                <p style="color:#4a5568;">
                    <strong>Guests</strong> {{ $booking->guests ?? '—' }}
                </p>
            </div>
        </div>

        <div style="padding:20px;">
            <h3 style="margin-bottom:8px; font-size:16px; color:#2d3748;">Pricing</h3>
            <p style="color:#4a5568;">
                <strong>Price</strong>
                @if($booking->price !== null)
                    {{ $booking->currency ?? 'EUR' }} {{ number_format((float)$booking->price, 2) }}
                @else
                    —
                @endif
            </p>
            <p style="color:#4a5568;">
                <strong>Estimated total</strong>
                @if($booking->estimated_total !== null)
                    {{ $booking->currency ?? 'EUR' }} {{ number_format((float)$booking->estimated_total, 2) }}
                @else
                    —
                @endif
            </p>
        </div>

        @if($booking->special_requests)
            <div style="padding:20px;">
                <h3 style="margin-bottom:8px; font-size:16px; color:#2d3748;">Special requests</h3>
                <p style="color:#4a5568; white-space:pre-wrap;">{{ $booking->special_requests }}</p>
            </div>
        @endif

        @if($booking->status !== 'cancelled')
            <div style="padding:20px; border-top:1px solid #e2e8f0;">
                <h3 style="margin-bottom:12px; font-size:16px; color:#2d3748;">Update status</h3>
                <form action="{{ route('admin.guide-bookings.status', $booking->id) }}" method="POST" style="display:flex; gap:8px; flex-wrap:wrap;">
                    @csrf
                    <select name="status" style="padding:8px 12px; border:2px solid #e2e8f0; border-radius:6px;">
                        <option value="pending" {{ $booking->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ $booking->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="cancelled" {{ $booking->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button type="submit" style="padding:8px 16px; background:#4299e1; color:white; border:none; border-radius:6px; cursor:pointer;">
                        Update
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection

