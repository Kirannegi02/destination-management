@extends('admin.layouts.app')

@section('title', 'User #' . $user->id . ' – ' . $user->name)
@section('page-title', 'User #' . $user->id . ' – ' . $user->name)

@section('content')
    {{-- Header card with user info --}}
    <div class="card" style="margin-bottom: 24px; border-left: 4px solid #4f46e5; background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; padding: 20px 24px;">
            <div>
                <h2 class="card-title" style="margin-bottom: 6px; color: #1e293b; font-size: 1.5rem;">{{ $user->name }}</h2>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 8px;">
                    {{ $user->agency_name ?? 'No agency' }} · {{ $user->email ?? '—' }} · {{ $user->phone ?? '—' }}
                </p>
                <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600;
                    @if($user->status === 'active') background: #dcfce7; color: #166534;
                    @elseif($user->status === 'inactive') background: #fee2e2; color: #991b1b;
                    @else background: #fef3c7; color: #92400e;
                    @endif">
                    {{ ucfirst($user->status) }}
                </span>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="{{ route('admin.users.index', ['status' => $user->status === 'pending' ? 'pending' : 'approved']) }}"
                   style="padding: 10px 18px; background: #f1f5f9; color: #475569; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500; border: 1px solid #e2e8f0;">
                    ← Back to users
                </a>
                <a href="{{ route('admin.users.edit', ['user' => $user->id, 'status' => $user->status === 'pending' ? 'pending' : 'approved']) }}"
                   style="padding: 10px 18px; background: #4f46e5; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500;">
                    Edit user
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: linear-gradient(145deg, #4f46e5 0%, #6366f1 100%); color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);">
            <p style="font-size: 12px; opacity: 0.9; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Total bookings</p>
            <p style="font-size: 28px; font-weight: 700; margin: 0;">{{ $totalBookings }}</p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #f59e0b; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">🍽️ Restaurant</p>
            <p style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0;">{{ $stats['restaurant']['total'] }}</p>
            <p style="font-size: 11px; color: #94a3b8; margin-top: 6px;">{{ $stats['restaurant']['pending'] }} pending · {{ $stats['restaurant']['confirmed'] }} confirmed</p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">🧭 Guide</p>
            <p style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0;">{{ $stats['guide']['total'] }}</p>
            <p style="font-size: 11px; color: #94a3b8; margin-top: 6px;">{{ $stats['guide']['pending'] }} pending · {{ $stats['guide']['confirmed'] }} confirmed</p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">🏔️ Sightseeing</p>
            <p style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0;">{{ $stats['sightseeing']['total'] }}</p>
            <p style="font-size: 11px; color: #94a3b8; margin-top: 6px;">{{ $stats['sightseeing']['pending'] }} pending · {{ $stats['sightseeing']['confirmed'] }} confirmed</p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #8b5cf6; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">🎁 Souvenir</p>
            <p style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0;">{{ $stats['souvenir']['total'] }}</p>
            <p style="font-size: 11px; color: #94a3b8; margin-top: 6px;">{{ $stats['souvenir']['pending'] }} pending · {{ $stats['souvenir']['confirmed'] }} confirmed</p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #ec4899; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">🚐 Transport</p>
            <p style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0;">{{ $stats['transport']['total'] }}</p>
            <p style="font-size: 11px; color: #94a3b8; margin-top: 6px;">{{ $stats['transport']['pending'] }} pending · {{ $stats['transport']['confirmed'] }} confirmed</p>
        </div>
    </div>

    {{-- Profile & summary row --}}
    <div style="display: grid; grid-template-columns: 1.3fr 1fr; gap: 24px; margin-bottom: 24px;">
        <div class="card" style="border-left: 4px solid #64748b;">
            <div class="card-header" style="background: #f8fafc; padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                <h3 style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600;">Profile</h3>
            </div>
            <div style="padding: 20px; color: #475569; font-size: 14px; line-height: 1.8;">
                <p style="margin: 0 0 8px;"><strong style="color: #334155;">Agency</strong> {{ $user->agency_name ?? '—' }}</p>
                <p style="margin: 0 0 8px;"><strong style="color: #334155;">Email</strong> {{ $user->email ?? '—' }}</p>
                <p style="margin: 0 0 8px;"><strong style="color: #334155;">Phone</strong> {{ $user->phone ?? '—' }}</p>
                @if($user->alternate_phone)
                    <p style="margin: 0 0 8px;"><strong style="color: #334155;">Alternate phone</strong> {{ $user->alternate_phone }}</p>
                @endif
                <p style="margin: 0 0 8px;"><strong style="color: #334155;">Tax number</strong> {{ $user->tax_number ?? '—' }}</p>
                <p style="margin: 0 0 8px;"><strong style="color: #334155;">Address</strong> {{ $user->address ?? '—' }}</p>
                <p style="margin: 0 0 8px;"><strong style="color: #334155;">Location</strong> {{ trim(($user->city ? $user->city . ', ' : '') . ($user->state ?? '') . ' ' . ($user->country ?? '')) ?: '—' }}</p>
                <p style="margin: 0 0 8px;"><strong style="color: #334155;">Joined</strong> {{ $user->created_at?->format('d M Y, H:i') }}</p>
            </div>
        </div>
        <div class="card" style="border-left: 4px solid #4f46e5;">
            <div class="card-header" style="background: #eef2ff; padding: 14px 20px; border-bottom: 1px solid #c7d2fe;">
                <h3 style="margin: 0; font-size: 16px; color: #3730a3; font-weight: 600;">Summary</h3>
            </div>
            <div style="padding: 20px;">
                <p style="margin: 0 0 12px; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Amounts (from listed orders)</p>
                @if($totals['restaurant']['amount'] > 0)
                    <p style="margin: 0 0 8px; padding: 10px 12px; background: #fffbeb; color: #92400e; border-radius: 8px; font-size: 14px;">🍽️ Restaurant total: <strong>{{ $totals['restaurant']['currency'] }} {{ number_format($totals['restaurant']['amount'], 2) }}</strong></p>
                @endif
                @if($totals['guide']['amount'] > 0)
                    <p style="margin: 0 0 8px; padding: 10px 12px; background: #ecfeff; color: #0e7490; border-radius: 8px; font-size: 14px;">🧭 Guide total: <strong>{{ $totals['guide']['currency'] }} {{ number_format($totals['guide']['amount'], 2) }}</strong></p>
                @endif
                @if($totals['sightseeing']['amount'] > 0)
                    <p style="margin: 0 0 8px; padding: 10px 12px; background: #ecfdf5; color: #047857; border-radius: 8px; font-size: 14px;">🏔️ Sightseeing total: <strong>{{ $totals['sightseeing']['currency'] }} {{ number_format($totals['sightseeing']['amount'], 2) }}</strong></p>
                @endif
                @if($totals['souvenir']['amount'] > 0)
                    <p style="margin: 0 0 8px; padding: 10px 12px; background: #f5f3ff; color: #5b21b6; border-radius: 8px; font-size: 14px;">🎁 Souvenir total: <strong>{{ $totals['souvenir']['currency'] }} {{ number_format($totals['souvenir']['amount'], 2) }}</strong></p>
                @endif
                @if($totals['transport']['amount'] > 0)
                    <p style="margin: 0 0 16px; padding: 10px 12px; background: #fdf2f8; color: #be185d; border-radius: 8px; font-size: 14px;">🚐 Transport total: <strong>{{ $totals['transport']['currency'] }} {{ number_format($totals['transport']['amount'], 2) }}</strong></p>
                @endif
                @if($totals['restaurant']['amount'] <= 0 && $totals['guide']['amount'] <= 0 && $totals['sightseeing']['amount'] <= 0 && $totals['souvenir']['amount'] <= 0 && $totals['transport']['amount'] <= 0)
                    <p style="margin: 0 0 16px; color: #94a3b8; font-size: 14px;">No monetary totals from listed orders.</p>
                @endif
                <p style="margin: 16px 0 8px; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Counts</p>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 14px; color: #475569;">
                    <li style="padding: 6px 0; border-bottom: 1px solid #f1f5f9;">🍽️ Restaurant bookings <strong style="color: #1e293b;">{{ $stats['restaurant']['total'] }}</strong></li>
                    <li style="padding: 6px 0; border-bottom: 1px solid #f1f5f9;">🧭 Guide bookings <strong style="color: #1e293b;">{{ $stats['guide']['total'] }}</strong></li>
                    <li style="padding: 6px 0; border-bottom: 1px solid #f1f5f9;">🏔️ Sightseeing <strong style="color: #1e293b;">{{ $stats['sightseeing']['total'] }}</strong></li>
                    <li style="padding: 6px 0; border-bottom: 1px solid #f1f5f9;">🎁 Souvenir orders <strong style="color: #1e293b;">{{ $stats['souvenir']['total'] }}</strong></li>
                    <li style="padding: 6px 0;">🚐 Transport requests <strong style="color: #1e293b;">{{ $stats['transport']['total'] }}</strong></li>
                </ul>
            </div>
        </div>
    </div>

    @php
        $statusBadge = function($status) {
            $status = strtolower($status);
            if (in_array($status, ['pending'])) return ['bg' => '#fef3c7', 'color' => '#92400e'];
            if (in_array($status, ['confirmed', 'shipped', 'delivered', 'request_review'])) return ['bg' => '#dcfce7', 'color' => '#166534'];
            if ($status === 'cancelled') return ['bg' => '#fee2e2', 'color' => '#991b1b'];
            return ['bg' => '#f1f5f9', 'color' => '#475569'];
        };
    @endphp

    {{-- Restaurant bookings --}}
    <div class="card" style="margin-bottom: 24px; border-left: 4px solid #f59e0b;">
        <div class="card-header" style="background: #fffbeb; padding: 14px 20px; border-bottom: 1px solid #fde68a;">
            <h3 class="card-title" style="margin: 0; font-size: 16px; color: #92400e; font-weight: 600;">🍽️ Restaurant bookings</h3>
            <p style="margin: 4px 0 0; color: #b45309; font-size: 13px;">{{ $restaurantBookings->count() }} booking(s)</p>
        </div>
        <div style="padding: 16px;">
            @if($restaurantBookings->isEmpty())
                <p style="color: #94a3b8; padding: 24px; text-align: center;">No restaurant bookings.</p>
            @else
                <div style="overflow-x: auto;">
                    <table class="table" style="font-size: 13px; width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">ID</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Restaurant</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Meal</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Date</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Time</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Guests</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($restaurantBookings as $b)
                                <tr style="border-bottom: 1px solid #f1f5f9; {{ $loop->iteration % 2 === 0 ? 'background: #fafafa;' : '' }}">
                                    <td style="padding: 12px;">#{{ $b->id }}</td>
                                    <td style="padding: 12px;">{{ $b->restaurant?->restaurant_name ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->meal ? ($b->meal->meal_type_label ?? $b->meal->meal_type ?? 'Meal') : ($b->meal_type ?? '—') }}</td>
                                    <td style="padding: 12px;">{{ $b->booking_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->booking_time ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->guests ?? '—' }}</td>
                                    <td style="padding: 12px;">
                                        @php $sb = $statusBadge($b->status); @endphp
                                        <span style="padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: {{ $sb['bg'] }}; color: {{ $sb['color'] }};">{{ ucfirst($b->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Guide bookings --}}
    <div class="card" style="margin-bottom: 24px; border-left: 4px solid #0ea5e9;">
        <div class="card-header" style="background: #ecfeff; padding: 14px 20px; border-bottom: 1px solid #a5f3fc;">
            <h3 class="card-title" style="margin: 0; font-size: 16px; color: #0e7490; font-weight: 600;">🧭 Guide bookings</h3>
            <p style="margin: 4px 0 0; color: #0891b2; font-size: 13px;">{{ $guideBookings->count() }} booking(s)</p>
        </div>
        <div style="padding: 16px;">
            @if($guideBookings->isEmpty())
                <p style="color: #94a3b8; padding: 24px; text-align: center;">No guide bookings.</p>
            @else
                <div style="overflow-x: auto;">
                    <table class="table" style="font-size: 13px; width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">ID</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Guide</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Package</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Service date</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Guests</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($guideBookings as $b)
                                <tr style="border-bottom: 1px solid #f1f5f9; {{ $loop->iteration % 2 === 0 ? 'background: #fafafa;' : '' }}">
                                    <td style="padding: 12px;">#{{ $b->id }}</td>
                                    <td style="padding: 12px;">{{ $b->guide?->full_name ?? $b->guide?->title ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->package?->service_name ?? $b->package?->service_type ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->service_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->guests ?? '—' }}</td>
                                    <td style="padding: 12px;">
                                        @php $sb = $statusBadge($b->status); @endphp
                                        <span style="padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: {{ $sb['bg'] }}; color: {{ $sb['color'] }};">{{ ucfirst($b->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Sightseeing bookings --}}
    <div class="card" style="margin-bottom: 24px; border-left: 4px solid #10b981;">
        <div class="card-header" style="background: #ecfdf5; padding: 14px 20px; border-bottom: 1px solid #a7f3d0;">
            <h3 class="card-title" style="margin: 0; font-size: 16px; color: #047857; font-weight: 600;">🏔️ Sightseeing bookings</h3>
            <p style="margin: 4px 0 0; color: #059669; font-size: 13px;">{{ $sightseeingBookings->count() }} booking(s)</p>
        </div>
        <div style="padding: 16px;">
            @if($sightseeingBookings->isEmpty())
                <p style="color: #94a3b8; padding: 24px; text-align: center;">No sightseeing bookings.</p>
            @else
                <div style="overflow-x: auto;">
                    <table class="table" style="font-size: 13px; width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">ID</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Sightseeing</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Option</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Date</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Pax</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sightseeingBookings as $b)
                                <tr style="border-bottom: 1px solid #f1f5f9; {{ $loop->iteration % 2 === 0 ? 'background: #fafafa;' : '' }}">
                                    <td style="padding: 12px;">#{{ $b->id }}</td>
                                    <td style="padding: 12px;">{{ $b->sightseeing?->title ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->sightseeingOption?->name ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->booking_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->pax_count ?? '—' }}</td>
                                    <td style="padding: 12px;">
                                        @php $sb = $statusBadge($b->status); @endphp
                                        <span style="padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: {{ $sb['bg'] }}; color: {{ $sb['color'] }};">{{ ucfirst($b->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Souvenir orders --}}
    <div class="card" style="margin-bottom: 24px; border-left: 4px solid #8b5cf6;">
        <div class="card-header" style="background: #f5f3ff; padding: 14px 20px; border-bottom: 1px solid #ddd6fe;">
            <h3 class="card-title" style="margin: 0; font-size: 16px; color: #5b21b6; font-weight: 600;">🎁 Souvenir orders</h3>
            <p style="margin: 4px 0 0; color: #6d28d9; font-size: 13px;">{{ $souvenirOrders->count() }} order(s)</p>
        </div>
        <div style="padding: 16px;">
            @if($souvenirOrders->isEmpty())
                <p style="color: #94a3b8; padding: 24px; text-align: center;">No souvenir orders.</p>
            @else
                <div style="overflow-x: auto;">
                    <table class="table" style="font-size: 13px; width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">ID</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Delivery date</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Total</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Shipping</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($souvenirOrders as $o)
                                <tr style="border-bottom: 1px solid #f1f5f9; {{ $loop->iteration % 2 === 0 ? 'background: #fafafa;' : '' }}">
                                    <td style="padding: 12px;">#{{ $o->id }}</td>
                                    <td style="padding: 12px;">{{ $o->requested_delivery_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td style="padding: 12px;">{{ $o->currency }} {{ number_format((float)$o->total, 2) }}</td>
                                    <td style="padding: 12px;">{{ $o->currency }} {{ number_format((float)$o->shipping_cost, 2) }}</td>
                                    <td style="padding: 12px;">
                                        @php $sb = $statusBadge($o->status); @endphp
                                        <span style="padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: {{ $sb['bg'] }}; color: {{ $sb['color'] }};">{{ str_replace('_', ' ', ucfirst($o->status)) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Transport quote requests --}}
    <div class="card" style="margin-bottom: 24px; border-left: 4px solid #ec4899;">
        <div class="card-header" style="background: #fdf2f8; padding: 14px 20px; border-bottom: 1px solid #fbcfe8;">
            <h3 class="card-title" style="margin: 0; font-size: 16px; color: #be185d; font-weight: 600;">🚐 Transport quote requests</h3>
            <p style="margin: 4px 0 0; color: #db2777; font-size: 13px;">{{ $transportBookings->count() }} request(s)</p>
        </div>
        <div style="padding: 16px;">
            @if($transportBookings->isEmpty())
                <p style="color: #94a3b8; padding: 24px; text-align: center;">No transport quote requests.</p>
            @else
                <div style="overflow-x: auto;">
                    <table class="table" style="font-size: 13px; width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">ID</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Trip type</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Passengers</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Cities</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Total amount</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transportBookings as $b)
                                <tr style="border-bottom: 1px solid #f1f5f9; {{ $loop->iteration % 2 === 0 ? 'background: #fafafa;' : '' }}">
                                    <td style="padding: 12px;">#{{ $b->id }}</td>
                                    <td style="padding: 12px;">{{ ucfirst(str_replace('_',' ', $b->trip_type)) }}</td>
                                    <td style="padding: 12px;">{{ $b->passengers }}</td>
                                    <td style="padding: 12px;">{{ $b->cities ? implode(' → ', $b->cities) : '—' }}</td>
                                    <td style="padding: 12px;">{{ $b->total_amount !== null ? (($b->currency ?? '') . ' ' . number_format((float)$b->total_amount, 2)) : '—' }}</td>
                                    <td style="padding: 12px;">
                                        @php $sb = $statusBadge($b->status); @endphp
                                        <span style="padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: {{ $sb['bg'] }}; color: {{ $sb['color'] }};">{{ ucfirst($b->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
