@extends('admin.layouts.app')

@section('title', 'Souvenir Orders')
@section('page-title', 'Souvenir Orders')

@section('content')
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 class="card-title" style="margin-bottom: 4px;">Souvenir Orders</h2>
                <p style="color: #718096; font-size: 14px;">Orders/booking requests for souvenirs.</p>
            </div>
        </div>

        <div style="padding: 16px; background: #f7fafc; border-bottom: 1px solid #e2e8f0;">
            <form action="{{ route('admin.souvenir-orders.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
                <div>
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Search</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Name / email / phone / order ID / city"
                           style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; min-width: 240px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Delivery date</label>
                    <input type="date"
                           name="delivery_date"
                           value="{{ request('delivery_date') }}"
                           style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Status</label>
                    <select name="status" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="request_review" {{ request('status') == 'request_review' ? 'selected' : '' }}>Request Review</option>
                    </select>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">Filter</button>
                    <a href="{{ route('admin.souvenir-orders.index') }}"
                       style="padding: 8px 16px; background: #e2e8f0; color: #4a5568; border-radius: 6px; text-decoration: none; font-size: 14px;">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        @if($orders->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon">🎁</div>
                <p>No souvenir orders found.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f7fafc; text-align: left;">
                            <th style="padding: 12px;">ID</th>
                            <th style="padding: 12px;">User</th>
                            <th style="padding: 12px;">Delivery Date</th>
                            <th style="padding: 12px;">Total</th>
                            <th style="padding: 12px;">Shipping</th>
                            <th style="padding: 12px;">Status</th>
                            <th style="padding: 12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px;">#{{ $order->id }}</td>
                                <td style="padding: 12px;">
                                    @if($order->user)
                                        {{ $order->user->name }}<br>
                                        <small style="color: #718096;">{{ $order->user->email ?? $order->user->phone }}</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="padding: 12px;">{{ $order->requested_delivery_date?->format('Y-m-d') }}</td>
                                <td style="padding: 12px;">{{ $order->currency }} {{ number_format((float)$order->total, 2) }}</td>
                                <td style="padding: 12px;">{{ $order->currency }} {{ number_format((float)$order->shipping_cost, 2) }}</td>
                                <td style="padding: 12px;"><span class="badge badge-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'cancelled' ? 'danger' : 'success') }}">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span></td>
                                <td style="padding: 12px;">
                                    <a href="{{ route('admin.souvenir-orders.show', $order->id) }}" style="color: #4299e1; text-decoration: none;">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div style="padding: 16px; border-top: 1px solid #e2e8f0;">{{ $orders->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
@endsection
