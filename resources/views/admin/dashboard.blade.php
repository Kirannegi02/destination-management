@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
    <style>
        .stat-card-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
    </style>
@endpush

@section('content')
    <!-- Top summary cards -->
    @php
        $dashboardCards = [
            [
                'title' => 'Total Guides',
                'value' => $stats['total_guides'] ?? 0,
                'icon' => '🧑‍💼',
                'iconStyle' => 'background: #dbeafe; color: #1e40af;',
                'href' => route('admin.guides.index'),
                'meta' => 'View all · Add new',
            ],
            [
                'title' => 'Active Guides',
                'value' => $stats['active_guides'] ?? 0,
                'icon' => '✅',
                'iconStyle' => 'background: #c6f6d5; color: #22543d;',
                'href' => route('admin.guides.index', ['status' => 'active']),
                'meta' => 'Currently active',
            ],
            [
                'title' => 'Total Restaurants',
                'value' => $stats['total_restaurants'] ?? 0,
                'icon' => '🍽️',
                'iconStyle' => 'background: #dbeafe; color: #1e40af;',
                'href' => route('admin.restaurants.index'),
                'meta' => 'View all · Add new',
            ],
            [
                'title' => 'Active Restaurants',
                'value' => $stats['active_restaurants'] ?? 0,
                'icon' => '✅',
                'iconStyle' => 'background: #c6f6d5; color: #22543d;',
                'href' => route('admin.restaurants.index', ['status' => 'active']),
                'meta' => 'Currently active',
            ],
            [
                'title' => 'Total Sightseeings',
                'value' => $stats['total_sightseeings'] ?? 0,
                'icon' => '🗺️',
                'iconStyle' => 'background: #dbeafe; color: #1e40af;',
                'href' => route('admin.sightseeings.index'),
                'meta' => 'View all · Add new',
            ],
            [
                'title' => 'Active Sightseeings',
                'value' => $stats['active_sightseeings'] ?? 0,
                'icon' => '✅',
                'iconStyle' => 'background: #c6f6d5; color: #22543d;',
                'href' => route('admin.sightseeings.index', ['status' => 'active']),
                'meta' => 'Currently active',
            ],
            [
                'title' => 'Total Transports',
                'value' => $stats['total_transports'] ?? 0,
                'icon' => '🚐',
                'iconStyle' => 'background: #e0f2fe; color: #0369a1;',
                'href' => route('admin.transports.index'),
                'meta' => 'View all',
            ],
            [
                'title' => 'Active Transports',
                'value' => $stats['active_transports'] ?? 0,
                'icon' => '✅',
                'iconStyle' => 'background: #dcfce7; color: #15803d;',
                'href' => route('admin.transports.index', ['status' => 'active']),
                'meta' => 'Currently active',
            ],
            [
                'title' => 'Total Souvenirs',
                'value' => $stats['total_souvenirs'] ?? 0,
                'icon' => '🎁',
                'iconStyle' => 'background: #fef3c7; color: #c2410c;',
                'href' => route('admin.souvenirs.index'),
                'meta' => 'View all',
            ],
            [
                'title' => 'Active Souvenirs',
                'value' => $stats['active_souvenirs'] ?? 0,
                'icon' => '✅',
                'iconStyle' => 'background: #fce7f3; color: #be185d;',
                'href' => route('admin.souvenirs.index', ['status' => 'active']),
                'meta' => 'Currently active',
            ],
        ];
    @endphp

    <div class="stats-grid">
        @foreach($dashboardCards as $card)
            <a href="{{ $card['href'] }}" class="stat-card stat-card-link">
                <div class="stat-header">
                    <div class="stat-title">{{ $card['title'] }}</div>
                    <div class="stat-icon" style="{{ $card['iconStyle'] }}">{{ $card['icon'] }}</div>
                </div>
                <div class="stat-value">{{ number_format($card['value']) }}</div>
                <div class="stat-change">{{ $card['meta'] }}</div>
            </a>
        @endforeach
    </div>

    <!-- Graphs row -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;">
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <h2 class="card-title">Modules Growth ({{ $charts['year'] ?? date('Y') }})</h2>
                <span style="font-size: 12px; color:#718096;">Guides · Restaurants · Sightseeings · Transport · Souvenirs</span>
            </div>
            <div style="padding: 10px 20px 20px;">
                <canvas id="modulesChart" height="120"></canvas>
            </div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #4f46e5, #ec4899); color: #fff;">
            <div class="card-header" style="border-bottom: none;">
                <h2 class="card-title" style="color:#fff;">Overview Snapshot</h2>
            </div>
            <div style="padding: 10px 20px 20px; display:grid; grid-template-columns:1fr 1fr; row-gap:10px; column-gap:20px; font-size:14px;">
                <div>
                    <div style="opacity:.8;">Guides</div>
                    <div style="font-size:20px; font-weight:700;">{{ number_format($stats['total_guides'] ?? 0) }}</div>
                </div>
                <div>
                    <div style="opacity:.8;">Restaurants</div>
                    <div style="font-size:20px; font-weight:700;">{{ number_format($stats['total_restaurants'] ?? 0) }}</div>
                </div>
                <div>
                    <div style="opacity:.8;">Sightseeings</div>
                    <div style="font-size:20px; font-weight:700;">{{ number_format($stats['total_sightseeings'] ?? 0) }}</div>
                </div>
                <div>
                    <div style="opacity:.8;">Transports</div>
                    <div style="font-size:20px; font-weight:700;">{{ number_format($stats['total_transports'] ?? 0) }}</div>
                </div>
                <div>
                    <div style="opacity:.8;">Souvenirs</div>
                    <div style="font-size:20px; font-weight:700;">{{ number_format($stats['total_souvenirs'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent data (Guides / Restaurants / Sightseeings / Transports / Souvenirs) -->
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
                                    @if($guide->half_day_price !== null && $guide->half_day_price !== '')
                                        HD €{{ number_format((float) $guide->half_day_price, 2) }}
                                        @if($guide->full_day_price !== null && $guide->full_day_price !== '')
                                            <br>FD €{{ number_format((float) $guide->full_day_price, 2) }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @php $status = strtolower($guide->status ?? 'active'); @endphp
                                    @if($status === 'active')
                                        <span class="badge badge-success">Active</span>
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
                                        €{{ number_format((float) $s->standard_price, 2) }}
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

        <!-- Recent Transports -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Transports</h2>
                <a href="{{ route('admin.transports.index') }}" style="color: #0ea5e9; text-decoration: none; font-size: 14px; font-weight: 500;">View All</a>
            </div>
            @if(isset($recentTransports) && $recentTransports->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Transport</th>
                            <th>City</th>
                            <th>Rate / Km</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTransports as $t)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.transports.show', $t->id) }}" style="color: #2d3748; text-decoration: none; font-weight: 600;">
                                        #{{ $t->id }} {{ $t->vehicle_name ?? 'Vehicle' }}
                                    </a>
                                </td>
                                <td>{{ $t->location ?? 'N/A' }}</td>
                                <td>
                                    @if($t->price_per_km !== null && $t->price_per_km !== '')
                                        €{{ number_format((float) $t->price_per_km, 2) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @php $status = strtolower($t->status ?? 'active'); @endphp
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
                    <div class="empty-state-icon">🚐</div>
                    <p>No transports found yet.</p>
                </div>
            @endif
        </div>

        <!-- Recent Souvenirs -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Souvenirs</h2>
                <a href="{{ route('admin.souvenirs.index') }}" style="color: #f97316; text-decoration: none; font-size: 14px; font-weight: 500;">View All</a>
            </div>
            @if(isset($recentSouvenirs) && $recentSouvenirs->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Souvenir</th>
                            <th>City</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSouvenirs as $sv)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.souvenirs.edit', $sv->id) }}" style="color: #2d3748; text-decoration: none; font-weight: 600;">
                                        #{{ $sv->id }} {{ $sv->name ?? 'Unnamed' }}
                                    </a>
                                </td>
                                <td>{{ $sv->city ?? $sv->country ?? 'N/A' }}</td>
                                <td>
                                    @if($sv->price !== null && $sv->price !== '')
                                        €{{ number_format((float) $sv->price, 2) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @php $status = strtolower($sv->status ?? 'active'); @endphp
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
                    <div class="empty-state-icon">🎁</div>
                    <p>No souvenirs found yet.</p>
                </div>
            @endif
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
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

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            (function () {
                const ctx = document.getElementById('modulesChart');
                if (!ctx) return;

                const labels = @json($charts['labels'] ?? []);
                const series = @json($charts['series'] ?? []);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Guides',
                                data: series.guides || [],
                                borderColor: '#4f46e5',
                                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                tension: 0.4,
                                fill: true,
                            },
                            {
                                label: 'Restaurants',
                                data: series.restaurants || [],
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                tension: 0.4,
                                fill: true,
                            },
                            {
                                label: 'Sightseeings',
                                data: series.sightseeings || [],
                                borderColor: '#f97316',
                                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                                tension: 0.4,
                                fill: true,
                            },
                            {
                                label: 'Transports',
                                data: series.transports || [],
                                borderColor: '#0ea5e9',
                                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                                tension: 0.4,
                                fill: true,
                            },
                            {
                                label: 'Souvenirs',
                                data: series.souvenirs || [],
                                borderColor: '#ec4899',
                                backgroundColor: 'rgba(236, 72, 153, 0.1)',
                                tension: 0.4,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            })();
        </script>
    @endpush
@endsection
