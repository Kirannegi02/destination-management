@extends('admin.layouts.app')

@section('title', 'Booking Details')
@section('page-title', 'Booking Details')

@section('content')
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 class="card-title" style="margin-bottom: 4px;">Booking #{{ $booking->id }}</h2>
                <p style="color: #718096; font-size: 14px;">Created at {{ $booking->created_at?->format('Y-m-d H:i') }}</p>
            </div>
            <a href="{{ route('admin.bookings.index') }}" 
               style="color: #667eea; text-decoration: none; font-size: 14px;">← Back to Bookings</a>
        </div>

        <div style="padding: 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="color: #2d3748;">Restaurant</h3>
                    <div style="display: flex; gap: 8px;">
                        @if($booking->status !== 'confirmed')
                            <form action="{{ route('admin.bookings.status', $booking->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" 
                                        style="padding: 6px 12px; background: #48bb78; color: white; border-radius: 6px; border: none; font-size: 12px; cursor: pointer;">
                                    Confirm
                                </button>
                            </form>
                        @endif
                        @if($booking->status !== 'cancelled')
                            <form action="{{ route('admin.bookings.status', $booking->id) }}" method="POST" style="display: inline;">
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
                @if($booking->restaurant)
                    <a href="{{ route('admin.restaurants.show', $booking->restaurant_id) }}" style="font-weight: 600; color: #2b6cb0; text-decoration: none;">
                        {{ $booking->restaurant->restaurant_name }}
                    </a>
                    <p style="color: #4a5568; margin-top: 4px;">ID: {{ $booking->restaurant_id }}</p>
                    @if($booking->restaurant->address)
                        <p style="color: #718096; margin-top: 8px;">{{ $booking->restaurant->address }}</p>
                    @endif
                @else
                    <p style="font-weight: 600;">N/A</p>
                @endif

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                    <h4 style="color: #2d3748; margin-bottom: 6px;">Meal</h4>
                    @if($booking->meal)
                        <p style="margin: 0; font-weight: 600;">
                            <a href="{{ route('admin.meals.show', $booking->meal_id) }}" style="color: #2b6cb0; text-decoration: none;">
                                {{ $booking->meal->meal_type_label }}
                            </a>
                        </p>
                    
                        @if($booking->meal_price_inr)
                            <p style="color: #48bb78; margin-top: 4px;">₹{{ number_format((float) $booking->meal_price_inr, 2) }}</p>
                        @endif
                        <p style="color: #718096; margin-top: 4px;">Meal ID: {{ $booking->meal_id }}</p>
                    @elseif($booking->meal_type)
                        <p style="margin: 0; font-weight: 600;">{{ $booking->meal_type }}</p>
                        @if($booking->meal_price_inr)
                            <p style="color: #48bb78; margin-top: 4px;">₹{{ number_format((float) $booking->meal_price_inr, 2) }}</p>
                        @endif
                    @else
                        <p style="color: #718096; margin: 0;">Not provided</p>
                    @endif
                </div>
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

        <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Check-In</h4>
                <p>{{ $booking->check_in?->format('Y-m-d H:i') }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Check-Out</h4>
                <p>{{ $booking->check_out?->format('Y-m-d H:i') }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Rooms</h4>
                <p>{{ $booking->rooms }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Guests</h4>
                <p>{{ $booking->guests }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Status</h4>
                <p style="font-weight: 600;">{{ ucfirst($booking->status) }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Estimated Total</h4>
                <p>
                    @if($booking->estimated_total !== null)
                        ₹{{ number_format((float) $booking->estimated_total, 2) }}
                    @else
                        N/A
                    @endif
                </p>
            </div>
        </div>

        <div style="padding: 20px;">
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Guest Details</h4>
                @if($booking->guests_details && is_array($booking->guests_details) && count($booking->guests_details))
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
                @else
                    <p style="color: #718096;">No guest details provided.</p>
                @endif
            </div>

            @if($booking->special_requests)
                <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                    <h4 style="margin-bottom: 8px; color: #2d3748;">Special Requests</h4>
                    <p style="color: #2d3748; line-height: 1.6;">{{ $booking->special_requests }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection

