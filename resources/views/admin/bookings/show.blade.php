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

                {{-- ── Meals Breakdown ─────────────────────────────────────── --}}
                @php
                    $mealsData = $booking->meals_data ?? [];
                    // Fall back to legacy scalar columns when no meals_data stored
                    if (empty($mealsData) && $booking->meal_id) {
                        $price    = $booking->meal_price ? (float) $booking->meal_price : null;
                        $subtotal = $price ? $price * ($booking->guests ?? 1) : null;
                        $mealsData = [[
                            'meal_id'         => $booking->meal_id,
                            'meal_type_label' => $booking->meal?->meal_type_label ?? $booking->meal_type ?? '—',
                            'menu_description'=> $booking->meal?->menu_description ?? null,
                            'price_per_person'=> $price,
                            'guests'          => $booking->guests,
                            'subtotal'        => $subtotal,
                        ]];
                    }
                @endphp

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                    <h4 style="color: #2d3748; margin-bottom: 10px;">
                        Meals
                        <span style="font-weight: 400; font-size: 13px; color: #718096;">
                            ({{ count($mealsData) }} {{ count($mealsData) === 1 ? 'item' : 'items' }})
                        </span>
                    </h4>

                    @if(count($mealsData))
                        @foreach($mealsData as $item)
                            @php
                                $supSelected = $item['supplements_selected'] ?? [];
                            @endphp
                            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 10px;">

                                {{-- Meal header --}}
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        @if(!empty($item['meal_id']))
                                            <a href="{{ route('admin.meals.show', $item['meal_id']) }}"
                                               style="font-weight: 600; color: #2b6cb0; text-decoration: none; font-size: 15px;">
                                                {{ $item['meal_type_label'] ?? '—' }}
                                            </a>
                                            <span style="color: #718096; font-size: 12px; margin-left: 6px;">
                                                ID: {{ $item['meal_id'] }}
                                            </span>
                                        @else
                                            <span style="font-weight: 600; font-size: 15px;">{{ $item['meal_type_label'] ?? '—' }}</span>
                                        @endif

                                        @if(!empty($item['menu_description']))
                                            <p style="color: #718096; font-size: 12px; margin: 3px 0 0;">
                                                {{ $item['menu_description'] }}
                                            </p>
                                        @endif
                                    </div>
                                    <div style="text-align: right;">
                                        @if(isset($item['price_per_person']) && $item['price_per_person'] !== null)
                                            <span style="font-size: 13px; color: #4a5568;">
                                                €{{ number_format((float)$item['price_per_person'], 2) }} × {{ $item['guests'] ?? '—' }} guests
                                            </span><br>
                                        @endif
                                        @if(isset($item['subtotal']) && $item['subtotal'] !== null)
                                            <span style="font-weight: 700; color: #2d3748; font-size: 15px;">
                                                €{{ number_format((float)$item['subtotal'], 2) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Supplements selected --}}
                                @if(!empty($supSelected))
                                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
                                        <p style="font-size: 12px; color: #718096; margin-bottom: 4px; font-weight: 600;">
                                            Supplements Added
                                        </p>
                                        @foreach($supSelected as $supKey => $sup)
                                            <div style="display: flex; justify-content: space-between; font-size: 13px; color: #4a5568; margin-bottom: 2px;">
                                                <span>+ {{ ucwords(str_replace('_', ' ', $supKey)) }}</span>
                                                <span>
                                                    @if(!empty($sup['price']))
                                                        €{{ number_format((float)$sup['price'], 2) }}
                                                        × {{ $item['guests'] ?? 1 }}
                                                        = <strong>€{{ number_format((float)$sup['price'] * ($item['guests'] ?? 1), 2) }}</strong>
                                                    @else
                                                        Price N/A
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                            </div>
                        @endforeach
                    @else
                        <p style="color: #718096; margin: 0;">No meal selected.</p>
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
                <h4 style="margin-bottom: 8px; color: #2d3748;">Date</h4>
                <p>{{ $booking->booking_date?->format('Y-m-d') ?? 'N/A' }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Time</h4>
                <p>{{ $booking->booking_time ?? 'N/A' }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Number of guests</h4>
                <p style="font-size: 22px; font-weight: 700; color: #2d3748; margin: 0;">{{ $booking->number_of_guests ?? $booking->guests ?? '—' }}</p>
                </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Status</h4>
                <p style="font-weight: 600;">{{ ucfirst($booking->status) }}</p>
            </div>
            <div style="background: #f7fafc; padding: 16px; border-radius: 8px;">
                <h4 style="margin-bottom: 8px; color: #2d3748;">Estimated Total</h4>
                @if($booking->estimated_total !== null)
                    <p style="font-size: 22px; font-weight: 700; color: #2d3748; margin: 0;">
                        €{{ number_format((float) $booking->estimated_total, 2) }}
                    </p>
                    @php
                        $mealsRows = $booking->meals_data ?? [];
                        $hasSupplements = collect($mealsRows)->contains(fn($m) => !empty($m['supplements_selected']));
                    @endphp
                    @if($hasSupplements)
                        <p style="font-size: 12px; color: #718096; margin-top: 4px;">
                            Includes meal price + supplement surcharges
                        </p>
                    @endif
                @else
                    <p style="color: #718096;">N/A</p>
                @endif
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
                                <th style="padding: 10px;">Email</th>
                                <th style="padding: 10px;">Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->guests_details as $guest)
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 10px;">{{ $guest['name'] ?? 'N/A' }}</td>
                                    <td style="padding: 10px;">{{ $guest['country'] ?? 'N/A' }}</td>
                                    <td style="padding: 10px;">
                                        @if(!empty($guest['email']))
                                            <a href="mailto:{{ $guest['email'] }}" style="color: #2b6cb0; text-decoration: none;">
                                                {{ $guest['email'] }}
                                            </a>
                                        @else
                                            <span style="color: #718096;">—</span>
                                        @endif
                                    </td>
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

