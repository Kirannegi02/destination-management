@extends('admin.layouts.app')

@section('title', 'Sightseeing Booking Details')
@section('page-title', 'Sightseeing Booking Details')

@section('content')
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 class="card-title" style="margin-bottom: 4px;">Sightseeing Booking #{{ $booking->id }}</h2>
                <p style="color: #718096; font-size: 14px;">Created at {{ $booking->created_at?->format('Y-m-d H:i') }}</p>
            </div>
            <a href="{{ route('admin.sightseeing-bookings.index') }}"
               style="color: #667eea; text-decoration: none; font-size: 14px;">← Back to Sightseeing Bookings</a>
        </div>

        @if(session('success'))
            <div style="margin: 20px; padding: 12px; background: #c6f6d5; color: #22543d; border-radius: 6px; border: 1px solid #9ae6b4;">
                {{ session('success') }}
            </div>
        @endif

        <div style="padding: 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="color: #2d3748;">Sightseeing</h3>
                    <div style="display: flex; gap: 8px;">
                        @if($booking->status !== 'confirmed')
                            <form action="{{ route('admin.sightseeing-bookings.status', $booking->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit"
                                        style="padding: 6px 12px; background: #48bb78; color: white; border-radius: 6px; border: none; font-size: 12px; cursor: pointer;">
                                    Confirm
                                </button>
                            </form>
                        @endif
                        @if($booking->status !== 'cancelled')
                            <form action="{{ route('admin.sightseeing-bookings.status', $booking->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit"
                                        style="padding: 6px 12px; background: #e53e3e; color: white; border-radius: 6px; border: none; font-size: 12px; cursor: pointer;">
                                    Cancel
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @if($booking->sightseeing)
                    <a href="{{ route('admin.sightseeings.show', $booking->sightseeing_id) }}" style="font-weight: 600; color: #2b6cb0; text-decoration: none;">
                        {{ $booking->sightseeing->title }}
                    </a>
                    <p style="color: #4a5568; margin-top: 4px;">ID: {{ $booking->sightseeing_id }}</p>
                    @if($booking->sightseeing->city || $booking->sightseeing->country)
                        <p style="color: #718096; margin-top: 8px;">{{ implode(', ', array_filter([$booking->sightseeing->city, $booking->sightseeing->country])) }}</p>
                    @endif
                @else
                    <p style="font-weight: 600;">N/A</p>
                @endif

                @if($booking->sightseeingOption)
                    <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                        <h4 style="color: #2d3748; margin-bottom: 6px;">Option / Variation</h4>
                        <p style="margin: 0; font-weight: 600;">{{ $booking->sightseeingOption->name }}</p>
                        <p style="color: #718096; margin-top: 4px;">ID: {{ $booking->sightseeing_option_id }}</p>
                    </div>
                @endif
            </div>

            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h3 style="margin-bottom: 12px; color: #2d3748;">Booked By</h3>
                @if($booking->user)
                    <a href="{{ route('admin.users.edit', $booking->user_id) }}" style="font-weight: 600; color: #2b6cb0; text-decoration: none;">
                        {{ $booking->user->name }}
                    </a>
                    <p style="color: #4a5568; margin-top: 4px;">User ID: {{ $booking->user_id }}</p>
                    @if($booking->user->email)
                        <p style="color: #718096; margin-top: 4px;">{{ $booking->user->email }}</p>
                    @endif
                    @if($booking->user->phone)
                        <p style="color: #718096; margin-top: 4px;">{{ $booking->user->phone }}</p>
                    @endif
                @else
                    <p style="font-weight: 600;">N/A</p>
                    <p style="color: #4a5568; margin-top: 4px;">User ID: {{ $booking->user_id }}</p>
                @endif
            </div>
        </div>

        <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Date</h4>
                <p>{{ $booking->booking_date?->format('Y-m-d') ?? 'N/A' }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Pax Count</h4>
                <p>{{ $booking->pax_count }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Price</h4>
                <p>
                    @if($booking->price !== null)
                        {{ $booking->currency ?? 'CHF' }} {{ number_format((float) $booking->price, 2) }}
                    @else
                        N/A
                    @endif
                </p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Status</h4>
                <p style="font-weight: 600;">{{ ucfirst($booking->status) }}</p>
            </div>
        </div>

        <div style="padding: 20px;">
            @if($booking->guest_name || $booking->guest_phone)
                <div style="background: #f7fafc; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                    <h4 style="margin-bottom: 8px; color: #2d3748;">Primary Guest</h4>
                    <p style="margin: 0;">{{ $booking->guest_name ?? '—' }}</p>
                    <p style="color: #718096; margin-top: 4px;">{{ $booking->guest_phone ?? '—' }}</p>
                </div>
            @endif

            @if($booking->guests_details && is_array($booking->guests_details) && count($booking->guests_details))
                <div style="background: #f7fafc; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                    <h4 style="margin-bottom: 8px; color: #2d3748;">Guest Details</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; background: #edf2f7;">
                                <th style="padding: 10px;">Name</th>
                                <th style="padding: 10px;">Country</th>
                                <th style="padding: 10px;">Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->guests_details as $guest)
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 10px;">{{ $guest['name'] ?? 'N/A' }}</td>
                                    <td style="padding: 10px;">{{ $guest['country'] ?? 'N/A' }}</td>
                                    <td style="padding: 10px;">{{ $guest['phone'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($booking->special_requests)
                <div style="background: #f7fafc; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                    <h4 style="margin-bottom: 8px; color: #2d3748;">Special Requests</h4>
                    <p style="color: #2d3748; line-height: 1.6;">{{ $booking->special_requests }}</p>
                </div>
            @endif

            @if($booking->booking_conditions_snapshot)
                <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                    <h4 style="margin-bottom: 8px; color: #2d3748;">Booking Conditions (at time of booking)</h4>
                    <div style="color: #2d3748; line-height: 1.6; white-space: pre-wrap;">{{ $booking->booking_conditions_snapshot }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection
