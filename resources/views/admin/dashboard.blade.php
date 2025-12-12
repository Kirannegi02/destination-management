@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total Restaurants</div>
                <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">🍽️</div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_restaurants'] ?? 0) }}</div>
            <div class="stat-change">All restaurants</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Active Restaurants</div>
                <div class="stat-icon" style="background: #c6f6d5; color: #22543d;">✅</div>
            </div>
            <div class="stat-value">{{ number_format($stats['active_restaurants'] ?? 0) }}</div>
            <div class="stat-change">Currently active</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Inactive Restaurants</div>
                <div class="stat-icon" style="background: #ffe4e6; color: #9b1c1c;">⏸️</div>
            </div>
            <div class="stat-value">{{ number_format($stats['inactive_restaurants'] ?? 0) }}</div>
            <div class="stat-change">Marked inactive</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total Bookings</div>
                <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">📋</div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_bookings'] ?? 0) }}</div>
            <div class="stat-change">All time bookings</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Pending Bookings</div>
                <div class="stat-icon" style="background: #feebc8; color: #7c2d12;">⏳</div>
            </div>
            <div class="stat-value">{{ number_format($stats['pending_bookings'] ?? 0) }}</div>
            <div class="stat-change">Awaiting confirmation</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Confirmed Bookings</div>
                <div class="stat-icon" style="background: #c6f6d5; color: #22543d;">✅</div>
            </div>
            <div class="stat-value">{{ number_format($stats['confirmed_bookings'] ?? 0) }}</div>
            <div class="stat-change">Successfully confirmed</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Cancelled Bookings</div>
                <div class="stat-icon" style="background: #ffe4e6; color: #9b1c1c;">❌</div>
            </div>
            <div class="stat-value">{{ number_format($stats['cancelled_bookings'] ?? 0) }}</div>
            <div class="stat-change">Total cancelled</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total Estimated</div>
                <div class="stat-icon" style="background: #d1fae5; color: #065f46;">💰</div>
            </div>
            <div class="stat-value">₹{{ number_format($stats['total_revenue'] ?? 0) }}</div>
            <div class="stat-change">From confirmed bookings</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Restaurant Bookings</h2>
                <a href="{{ route('admin.bookings.index') }}" style="color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500;">View All</a>
            </div>
            @if(isset($recentBookings) && $recentBookings->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Restaurant</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Guests / Rooms</th>
                            <th>Est. Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBookings as $booking)
                            <tr>
                                <td>#{{ $booking->id }}</td>
                                <td>{{ $booking->restaurant_name ?? 'N/A' }}</td>
                                <td>{{ $booking->check_in ? \Carbon\Carbon::parse($booking->check_in)->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td>{{ $booking->check_out ? \Carbon\Carbon::parse($booking->check_out)->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td>{{ $booking->guests ?? '0' }} / {{ $booking->rooms ?? '0' }}</td>
                                <td>
                                    @if($booking->estimated_total !== null)
                                        ₹{{ number_format((float) $booking->estimated_total, 2) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $status = strtolower($booking->status ?? 'pending');
                                    @endphp
                                    @if($status === 'confirmed')
                                        <span class="badge badge-success">Confirmed</span>
                                    @elseif($status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($status === 'cancelled')
                                        <span class="badge badge-danger">Cancelled</span>
                                    @else
                                        <span class="badge badge-info">{{ ucfirst($status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>No bookings found. Bookings will appear here once they are created.</p>
                </div>
            @endif
        </div>

        <!-- Notifications -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Notifications</h2>
            </div>
            @if(isset($notifications) && $notifications->count() > 0)
                <div>
                    @foreach($notifications as $notification)
                        <div class="notification-item">
                            <div class="notification-text">{{ $notification->message ?? 'No message' }}</div>
                            <div class="notification-date">{{ $notification->created_at ? \Carbon\Carbon::parse($notification->created_at)->format('d M Y') : 'N/A' }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">🔔</div>
                    <p>No notifications at the moment.</p>
                </div>
            @endif
        </div>
    </div>
@endsection



