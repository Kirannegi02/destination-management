@extends('admin.layouts.app')

@section('title', 'Souvenir Order #' . $order->id)
@section('page-title', 'Souvenir Order #' . $order->id)

@section('content')

    @if(session('success'))
        <div style="background:#c6f6d5; color:#276749; padding:12px 16px; border-radius:8px; margin-bottom:16px; border:1px solid #9ae6b4;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:20px; align-items:start;">

        {{-- LEFT: order details --}}
        <div>
            {{-- Order Items --}}
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 class="card-title">Order Items</h2>
                    <a href="{{ route('admin.souvenir-orders.index') }}" style="color:#667eea; text-decoration:none; font-size:14px;">← Back to Orders</a>
                </div>

                <div style="overflow-x:auto;">
                    <table class="table" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f7fafc; text-align:left;">
                                <th style="padding:12px;">Image</th>
                                <th style="padding:12px;">Souvenir</th>
                                <th style="padding:12px; text-align:right;">Unit Price</th>
                                <th style="padding:12px; text-align:right;">Qty</th>
                                <th style="padding:12px; text-align:right;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:12px;">
                                        @if($item->souvenir && $item->souvenir->images && count($item->souvenir->images) > 0)
                                            <img src="{{ \App\Services\ImageService::getUrl($item->souvenir->images[0]) }}"
                                                 alt="" style="width:50px; height:50px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0;">
                                        @else
                                            <span style="color:#a0aec0;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding:12px;">
                                        <strong style="color:#2d3748;">{{ $item->souvenir->name ?? '(deleted)' }}</strong>
                                        @if($item->souvenir?->country)
                                            <br><small style="color:#718096;">{{ $item->souvenir->country }}</small>
                                        @endif
                                    </td>
                                    <td style="padding:12px; text-align:right;">{{ $order->currency }} {{ number_format((float)$item->unit_price, 2) }}</td>
                                    <td style="padding:12px; text-align:right;">{{ $item->quantity }}</td>
                                    <td style="padding:12px; text-align:right; font-weight:600;">{{ $order->currency }} {{ number_format((float)$item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#f7fafc;">
                                <td colspan="4" style="padding:10px 12px; text-align:right; color:#4a5568;">Subtotal</td>
                                <td style="padding:10px 12px; text-align:right;">{{ $order->currency }} {{ number_format((float)$order->subtotal, 2) }}</td>
                            </tr>
                            <tr style="background:#f7fafc;">
                                <td colspan="4" style="padding:10px 12px; text-align:right; color:#4a5568;">
                                    Shipping
                                    @if($order->within_city)
                                        <span style="color:#38a169; font-size:12px;">(within city — free)</span>
                                    @endif
                                </td>
                                <td style="padding:10px 12px; text-align:right;">{{ $order->currency }} {{ number_format((float)$order->shipping_cost, 2) }}</td>
                            </tr>
                            <tr style="background:#ebf8ff;">
                                <td colspan="4" style="padding:12px; text-align:right; font-weight:700; font-size:15px; color:#2b6cb0;">Total</td>
                                <td style="padding:12px; text-align:right; font-weight:700; font-size:15px; color:#2b6cb0;">{{ $order->currency }} {{ number_format((float)$order->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Delivery & Notes --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Delivery Details</h2>
                </div>
                <div style="padding:20px; display:grid; gap:12px; font-size:14px;">
                    <div style="display:grid; grid-template-columns:180px 1fr; gap:8px;">
                        <span style="color:#718096; font-weight:600;">Requested Date</span>
                        <span>{{ $order->requested_delivery_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    @if($order->expected_delivery_at)
                        <div style="display:grid; grid-template-columns:180px 1fr; gap:8px;">
                            <span style="color:#718096; font-weight:600;">Expected Delivery</span>
                            <span>{{ $order->expected_delivery_at->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                    @if($order->delivery_location)
                        <div style="display:grid; grid-template-columns:180px 1fr; gap:8px;">
                            <span style="color:#718096; font-weight:600;">Delivery Location</span>
                            <span>{{ $order->delivery_location }}</span>
                        </div>
                    @endif
                    @if($order->userAddress)
                        <div style="display:grid; grid-template-columns:180px 1fr; gap:8px;">
                            <span style="color:#718096; font-weight:600;">Address</span>
                            <span>
                                {{ $order->userAddress->address_line_1 ?? '' }}
                                @if($order->userAddress->address_line_2) , {{ $order->userAddress->address_line_2 }} @endif
                                <br>{{ $order->userAddress->city ?? '' }}
                                @if($order->userAddress->state) , {{ $order->userAddress->state }} @endif
                                @if($order->userAddress->pincode) – {{ $order->userAddress->pincode }} @endif
                                <br>{{ $order->userAddress->country ?? '' }}
                            </span>
                        </div>
                    @endif
                    @if($order->delivery_too_close)
                        <div style="background:#fffaf0; border:1px solid #fbd38d; border-radius:6px; padding:10px 14px; color:#744210; font-size:13px;">
                            ⚠️ Delivery date is very close — please review before confirming.
                        </div>
                    @endif
                    @if($order->pending_restock)
                        <div style="background:#fff5f5; border:1px solid #feb2b2; border-radius:6px; padding:10px 14px; color:#742a2a; font-size:13px;">
                            ⚠️ Pending restock required.
                            @if($order->partial_stock_summary)
                                <br><small>{{ $order->partial_stock_summary }}</small>
                            @endif
                        </div>
                    @endif
                    @if($order->notes)
                        <div style="display:grid; grid-template-columns:180px 1fr; gap:8px;">
                            <span style="color:#718096; font-weight:600;">Notes</span>
                            <span style="white-space:pre-line;">{{ $order->notes }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT: status + customer --}}
        <div>
            {{-- Status update --}}
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h2 class="card-title">Order Status</h2>
                </div>
                <div style="padding:20px;">
                    @php
                        $statusColors = [
                            'pending'        => ['bg'=>'#fffaf0','text'=>'#c05621','border'=>'#fbd38d'],
                            'confirmed'      => ['bg'=>'#ebf8ff','text'=>'#2b6cb0','border'=>'#90cdf4'],
                            'shipped'        => ['bg'=>'#e9d8fd','text'=>'#553c9a','border'=>'#d6bcfa'],
                            'delivered'      => ['bg'=>'#f0fff4','text'=>'#276749','border'=>'#9ae6b4'],
                            'cancelled'      => ['bg'=>'#fff5f5','text'=>'#742a2a','border'=>'#feb2b2'],
                            'request_review' => ['bg'=>'#f7fafc','text'=>'#4a5568','border'=>'#e2e8f0'],
                        ];
                        $colors = $statusColors[$order->status] ?? $statusColors['request_review'];
                    @endphp
                    <div style="background:{{ $colors['bg'] }}; color:{{ $colors['text'] }}; border:1px solid {{ $colors['border'] }}; border-radius:8px; padding:12px 16px; font-weight:700; font-size:16px; text-align:center; margin-bottom:16px;">
                        {{ str_replace('_', ' ', ucfirst($order->status)) }}
                    </div>

                    <form action="{{ route('admin.souvenir-orders.status', $order->id) }}" method="POST">
                        @csrf
                        <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:600; color:#4a5568;">Update Status</label>
                        <select name="status" style="width:100%; padding:9px 12px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px; margin-bottom:10px;">
                            @foreach(['pending','confirmed','shipped','delivered','cancelled'] as $s)
                                <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" style="width:100%; padding:10px; background:#667eea; color:white; border:none; border-radius:8px; font-size:14px; cursor:pointer;">
                            Save Status
                        </button>
                    </form>
                </div>
            </div>

            {{-- Customer --}}
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h2 class="card-title">Customer</h2>
                </div>
                <div style="padding:20px; font-size:14px; display:grid; gap:10px;">
                    @if($order->user)
                        <div><span style="color:#718096;">Name:</span> <strong>{{ $order->user->name }}</strong></div>
                        @if($order->user->email)
                            <div><span style="color:#718096;">Email:</span> {{ $order->user->email }}</div>
                        @endif
                        @if($order->user->phone)
                            <div><span style="color:#718096;">Phone:</span> {{ $order->user->phone }}</div>
                        @endif
                    @else
                        <span style="color:#a0aec0;">No customer linked.</span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Actions</h2>
                </div>
                <div style="padding:16px; display:flex; flex-direction:column; gap:10px;">
                    <a href="{{ route('admin.souvenir-orders.invoice', $order->id) }}"
                       target="_blank"
                       style="display:block; text-align:center; padding:10px; background:#1e3a8a; color:white; border-radius:8px; text-decoration:none; font-size:14px;">
                        🖨️ View / Print Invoice
                    </a>
                    <a href="{{ route('admin.souvenir-orders.index') }}"
                       style="display:block; text-align:center; padding:10px; background:#e2e8f0; color:#4a5568; border-radius:8px; text-decoration:none; font-size:14px;">
                        ← Back to Orders
                    </a>
                </div>
            </div>
        </div>

    </div>
@endsection
