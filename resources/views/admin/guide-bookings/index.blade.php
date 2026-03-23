@extends('admin.layouts.app')

@section('title', 'Guide Bookings')
@section('page-title', 'Guide Bookings')

@section('content')
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 class="card-title" style="margin-bottom: 4px;">Guide Bookings</h2>
                <p style="color: #718096; font-size: 14px;">All guide bookings created by users/agents.</p>
            </div>
            <form method="GET" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                @php
                    $currentStatus = request('status', 'all');
                @endphp
                <div>
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">
                        Search
                    </label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Name / email / phone / booking ID"
                           style="padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 6px; min-width: 220px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">
                        Service date
                    </label>
                    <input type="date"
                           name="service_date"
                           value="{{ request('service_date') }}"
                           style="padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">
                        Status
                    </label>
                    <select name="status"
                            style="padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; min-width: 140px;">
                        <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All</option>
                        <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ $currentStatus === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit"
                            style="padding: 8px 16px; background: #4299e1; color: white; border-radius: 6px; border: none; font-size: 14px; cursor: pointer;">
                        Filter
                    </button>
                    <a href="{{ route('admin.guide_bookings.index') }}"
                       style="padding: 8px 16px; background: #e2e8f0; color: #4a5568; border-radius: 6px; text-decoration: none; font-size: 14px;">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        @if($bookings->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon">🧭</div>
                <p>No guide bookings found.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f7fafc; text-align: left;">
                            <th style="padding: 12px;">ID</th>
                            <th style="padding: 12px;">Guide</th>
                            <th style="padding: 12px;">Package</th>
                            <th style="padding: 12px;">Booked By</th>
                            <th style="padding: 12px;">Service Date / Time</th>
                            <th style="padding: 12px;">Location / Slot</th>
                            <th style="padding: 12px;">Guests</th>
                            <th style="padding: 12px;">Est. Total</th>
                            <th style="padding: 12px;">Status</th>
                            <th style="padding: 12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px;">#{{ $booking->id }}</td>
                                <td style="padding: 12px;">
                                    @if($booking->guide)
                                        <a href="{{ route('admin.guides.show', $booking->guide_id) }}" style="color: #2b6cb0; text-decoration: none; font-weight: 600;">
                                            {{ $booking->guide->full_name ?? $booking->guide->title }}
                                        </a><br>
                                    @else
                                        <strong>N/A</strong><br>
                                    @endif
                                    <small style="color: #718096;">ID: {{ $booking->guide_id }}</small>
                                </td>
                                <td style="padding: 12px;">
                                    @if($booking->package)
                                        <div style="font-weight: 600;">{{ $booking->package->service_name ?? $booking->package->service_type }}</div>
                                        @if($booking->package->standard_price)
                                            <div style="color: #48bb78;">₹{{ number_format((float) $booking->package->standard_price, 2) }}</div>
                                        @endif
                                        <small style="color: #718096;">ID: {{ $booking->guide_package_id }}</small>
                                    @else
                                        <span style="color: #718096;">N/A</span>
                                    @endif
                                </td>
                                <td style="padding: 12px;">
                                    @if($booking->user)
                                        <a href="{{ route('admin.users.edit', $booking->user_id) }}" style="color: #2b6cb0; text-decoration: none;">
                                            {{ $booking->user->name }}
                                        </a><br>
                                    @else
                                        N/A<br>
                                    @endif
                                    <small style="color: #718096;">ID: {{ $booking->user_id }}</small>
                                </td>
                                <td style="padding: 12px; white-space: nowrap;">
                                    <div>📅 {{ optional($booking->service_date)->format('Y-m-d') }}</div>
                                    <div>⏱️ {{ optional($booking->start_time)->format('H:i') ?? $booking->start_time_slot ?? 'N/A' }}</div>
                                </td>
                                <td style="padding: 12px;">
                                    <div><strong>From:</strong> {{ $booking->start_location ?? '—' }}</div>
                                    <div><strong>To:</strong> {{ $booking->end_location ?? '—' }}</div>
                                    <div><strong>Slot:</strong> {{ $booking->start_time_slot ?? '—' }}</div>
                                </td>
                                <td style="padding: 12px;">{{ $booking->guests ?? '—' }}</td>
                                <td style="padding: 12px;">
                                    @if($booking->estimated_total !== null)
                                        ₹{{ number_format((float) $booking->estimated_total, 2) }}
                                    @elseif($booking->price !== null)
                                        ₹{{ number_format((float) $booking->price, 2) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td style="padding: 12px;">
                                    @php
                                        $badge = [
                                            'pending' => ['text' => 'Pending', 'class' => 'badge badge-warning'],
                                            'confirmed' => ['text' => 'Confirmed', 'class' => 'badge badge-success'],
                                            'cancelled' => ['text' => 'Cancelled', 'class' => 'badge badge-danger'],
                                        ][$booking->status] ?? ['text' => ucfirst($booking->status), 'class' => 'badge'];
                                    @endphp
                                    <span class="{{ $badge['class'] }}">{{ $badge['text'] }}</span>
                                </td>
                                <td style="padding: 12px;">
                                    <a href="{{ route('admin.guide-bookings.show', $booking->id) }}"
                                       style="padding: 6px 12px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding: 16px;">
                {{ $bookings->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection


