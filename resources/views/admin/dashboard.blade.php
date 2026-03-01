@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Module Statistics (Guides / Restaurants / Sightseeings) -->
    <div class="stats-grid">
        <!-- Guides -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total Guides</div>
                <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">🧑‍💼</div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_guides'] ?? 0) }}</div>
            <div class="stat-change">
                <a href="{{ route('admin.guides.index') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">View all</a>
                · <a href="{{ route('admin.guides.create') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">Add new</a>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Active Guides</div>
                <div class="stat-icon" style="background: #c6f6d5; color: #22543d;">✅</div>
            </div>
            <div class="stat-value">{{ number_format($stats['active_guides'] ?? 0) }}</div>
            <div class="stat-change">Currently active</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Pending Guides</div>
                <div class="stat-icon" style="background: #feebc8; color: #7c2d12;">⏳</div>
            </div>
            <div class="stat-value">{{ number_format($stats['pending_guides'] ?? 0) }}</div>
            <div class="stat-change">Awaiting approval</div>
        </div>

        <!-- Restaurants -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total Restaurants</div>
                <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">🍽️</div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_restaurants'] ?? 0) }}</div>
            <div class="stat-change">
                <a href="{{ route('admin.restaurants.index') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">View all</a>
                · <a href="{{ route('admin.restaurants.create') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">Add new</a>
            </div>
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
                <div class="stat-title">Pending Restaurants</div>
                <div class="stat-icon" style="background: #feebc8; color: #7c2d12;">⏳</div>
            </div>
            <div class="stat-value">{{ number_format($stats['pending_restaurants'] ?? 0) }}</div>
            <div class="stat-change">Awaiting approval</div>
        </div>

        <!-- Sightseeings -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total Sightseeings</div>
                <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">🗺️</div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_sightseeings'] ?? 0) }}</div>
            <div class="stat-change">
                <a href="{{ route('admin.sightseeings.index') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">View all</a>
                · <a href="{{ route('admin.sightseeings.create') }}" style="color: #667eea; text-decoration: none; font-weight: 600;">Add new</a>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Active Sightseeings</div>
                <div class="stat-icon" style="background: #c6f6d5; color: #22543d;">✅</div>
            </div>
            <div class="stat-value">{{ number_format($stats['active_sightseeings'] ?? 0) }}</div>
            <div class="stat-change">Currently active</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Featured Sightseeings</div>
                <div class="stat-icon" style="background: #ede9fe; color: #5b21b6;">⭐</div>
            </div>
            <div class="stat-value">{{ number_format($stats['featured_sightseeings'] ?? 0) }}</div>
            <div class="stat-change">Marked featured</div>
        </div>
    </div>

    <!-- Recent data (Guides / Restaurants / Sightseeings) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; margin-bottom: 30px;">
        <!-- Recent Guides -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Guides</h2>
                <a href="{{ route('admin.guides.index') }}" style="color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500;">View All</a>
            </div>
            @if(isset($recentGuides) && $recentGuides->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Guide</th>
                            <th>City</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentGuides as $guide)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.guides.show', $guide->id) }}" style="color: #2d3748; text-decoration: none; font-weight: 600;">
                                        #{{ $guide->id }} {{ $guide->title ?? 'Untitled' }}
                                    </a>
                                </td>
                                <td>{{ $guide->city ?? 'N/A' }}</td>
                                <td>
                                    @if($guide->price !== null && $guide->price !== '')
                                        ₹{{ number_format((float) $guide->price, 2) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @php $status = strtolower($guide->status ?? 'active'); @endphp
                                    @if($status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @elseif($status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($status === 'inactive')
                                        <span class="badge badge-danger">Inactive</span>
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
                    <div class="empty-state-icon">🧑‍💼</div>
                    <p>No guides found yet.</p>
                </div>
            @endif
        </div>

        <!-- Recent Restaurants -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Restaurants</h2>
                <a href="{{ route('admin.restaurants.index') }}" style="color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500;">View All</a>
            </div>
            @if(isset($recentRestaurants) && $recentRestaurants->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Restaurant</th>
                            <th>City</th>
                            <th>Rating</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentRestaurants as $restaurant)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.restaurants.show', $restaurant->id) }}" style="color: #2d3748; text-decoration: none; font-weight: 600;">
                                        #{{ $restaurant->id }} {{ $restaurant->restaurant_name ?? 'Unnamed' }}
                                    </a>
                                </td>
                                <td>{{ $restaurant->city ?? 'N/A' }}</td>
                                <td>{{ $restaurant->star_rating ?? 'N/A' }}</td>
                                <td>
                                    @php $status = strtolower($restaurant->status ?? 'active'); @endphp
                                    @if($status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @elseif($status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($status === 'inactive')
                                        <span class="badge badge-danger">Inactive</span>
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
                    <div class="empty-state-icon">🍽️</div>
                    <p>No restaurants found yet.</p>
                </div>
            @endif
        </div>

        <!-- Recent Sightseeings -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Sightseeings</h2>
                <a href="{{ route('admin.sightseeings.index') }}" style="color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500;">View All</a>
            </div>
            @if(isset($recentSightseeings) && $recentSightseeings->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sightseeing</th>
                            <th>City</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSightseeings as $s)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.sightseeings.show', $s->id) }}" style="color: #2d3748; text-decoration: none; font-weight: 600;">
                                        #{{ $s->id }} {{ $s->title ?? 'Untitled' }}
                                        @if(!empty($s->is_featured))
                                            <span title="Featured">⭐</span>
                                        @endif
                                    </a>
                                </td>
                                <td>{{ $s->city ?? 'N/A' }}</td>
                                <td>
                                    @if($s->standard_price !== null && $s->standard_price !== '')
                                        {{ $s->currency ?? '₹' }}{{ number_format((float) $s->standard_price, 2) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @php $status = strtolower($s->status ?? 'active'); @endphp
                                    @if($status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @elseif($status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($status === 'inactive')
                                        <span class="badge badge-danger">Inactive</span>
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
                    <div class="empty-state-icon">🗺️</div>
                    <p>No sightseeings found yet.</p>
                </div>
            @endif
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
                            <th>Date</th>
                            <th>Time</th>
                            <th>Guests</th>
                            <th>Est. Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBookings as $booking)
                            <tr>
                                <td>#{{ $booking->id }}</td>
                                <td>{{ $booking->restaurant_name ?? 'N/A' }}</td>
                                <td>{{ $booking->booking_date ? \Carbon\Carbon::parse($booking->booking_date)->format('Y-m-d') : 'N/A' }}</td>
                                <td>{{ $booking->booking_time ?? 'N/A' }}</td>
                                <td>{{ $booking->guests ?? '0' }}</td>
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



