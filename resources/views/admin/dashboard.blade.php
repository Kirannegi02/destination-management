@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Statistics Cards -->
    <div class="stats-grid">
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
                <div class="stat-title">Total Revenue</div>
                <div class="stat-icon" style="background: #d1fae5; color: #065f46;">💰</div>
            </div>
            <div class="stat-value">₹{{ number_format($stats['total_revenue'] ?? 0) }}</div>
            <div class="stat-change">From confirmed bookings</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total Services</div>
                <div class="stat-icon" style="background: #e9d5ff; color: #6b21a8;">🏨</div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_services'] ?? 0) }}</div>
            <div class="stat-change">Available services</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Destinations</div>
                <div class="stat-icon" style="background: #fce7f3; color: #9f1239;">🌍</div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_destinations'] ?? 0) }}</div>
            <div class="stat-change">Active destinations</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Bookings Summary</h2>
                <a href="#" style="color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500;">View All</a>
            </div>
            @if(isset($recentBookings) && $recentBookings->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Service Type</th>
                            <th>Destination</th>
                            <th>Date</th>
                            <th>Pax</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBookings as $booking)
                            <tr>
                                <td>{{ $booking->booking_id ?? 'N/A' }}</td>
                                <td>{{ $booking->service_type ?? 'N/A' }}</td>
                                <td>{{ $booking->destination ?? 'N/A' }}</td>
                                <td>{{ $booking->booking_date ?? 'N/A' }}</td>
                                <td>{{ $booking->pax ?? 'N/A' }}</td>
                                <td>₹{{ number_format($booking->amount ?? 0) }}</td>
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



