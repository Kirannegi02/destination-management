@extends('admin.layouts.app')

@section('title', 'Souvenir Order #' . $order->id)
@section('page-title', 'Souvenir Order #' . $order->id)

@section('content')
    <div class="card" style="border:none; box-shadow: 0 10px 30px rgba(15,23,42,0.08); border-radius: 16px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding:16px 24px;">
            <div>
                <h2 class="card-title" style="font-size:20px; font-weight:700; color:#1e293b; margin-bottom:2px;">Order #{{ $order->id }}</h2>
                <div style="display:flex; gap:10px; align-items:center; font-size:12px; color:#64748b;">
                    <span>Placed on {{ $order->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
                    @if($order->requested_delivery_date)
                        <span style="width:3px; height:3px; border-radius:999px; background:#cbd5f5;"></span>
                        <span>Scheduled: {{ $order->requested_delivery_date?->format('d M Y') }}</span>
                    @endif
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                @if(!in_array($order->status, ['cancelled', 'delivered']))
                    <form action="{{ route('admin.souvenir-orders.status', $order->id) }}" method="POST" style="display:flex; align-items:center; gap:8px; margin:0;">
                        @csrf
                        <label style="font-weight: 600; font-size:13px; color:#374151;">Status</label>
                        <select name="status" onchange="this.form.submit()" style="flex:1; padding: 6px 10px; border: 2px solid #e2e8f0; border-radius: 999px; font-size: 13px;">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </form>
                @endif
                <button type="button"
                        onclick="window.location.href='{{ route('admin.souvenir-orders.invoice', $order->id) }}'"
                        style="padding:8px 16px; border-radius:999px; border:none; background:#15803d; font-size:13px; color:#f9fafb; display:flex; align-items:center; gap:6px; cursor:pointer; box-shadow:0 4px 10px rgba(22,163,74,0.35);">
                    <span>🧾</span><span>Print Invoice</span>
                </button>
                <a href="{{ route('admin.souvenir-orders.index') }}"
                   style="color: #667eea; text-decoration: none; font-size: 14px; font-weight:500;">
                    ← Back to Orders
                </a>
            </div>
        </div>

        <div style="padding: 20px 24px 24px;">
            <div style="display:grid; grid-template-columns: minmax(0,2fr) minmax(0,1fr); gap:24px; align-items:flex-start;">
                <!-- Left: order items and notes -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <h3 style="margin:0; color: #1e293b; font-size:16px; font-weight:700;">Order Items</h3>
                        <span style="font-size:12px; color:#64748b;">{{ $order->items->count() }} item(s)</span>
                    </div>
                    <div style="border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; background:#ffffff;">
                        <table class="table" style="width: 100%; margin:0;">
                            <thead>
                                <tr style="background: #f8fafc; color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:.04em;">
                                    <th style="padding: 10px 14px; text-align:left;">Souvenir</th>
                                    <th style="padding: 10px 14px; text-align:center; width:80px;">Qty</th>
                                    <th style="padding: 10px 14px; text-align:right; width:120px;">Unit Price</th>
                                    <th style="padding: 10px 14px; text-align:right; width:120px;">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr style="border-top: 1px solid #e2e8f0; font-size:14px;">
                                        <td style="padding: 10px 14px;">
                                            <div style="font-weight:600; color:#111827;">
                                                {{ $item->souvenir?->name ?? 'Souvenir #' . $item->souvenir_id }}
                                            </div>
                                            @if($item->souvenir && $item->souvenir->city)
                                                <div style="font-size:12px; color:#6b7280; margin-top:2px;">
                                                    {{ $item->souvenir->city }}, {{ $item->souvenir->country ?? 'India' }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding: 10px 14px; text-align:center;">{{ $item->quantity }}</td>
                                        <td style="padding: 10px 14px; text-align:right;">{{ $order->currency }} {{ number_format((float)$item->unit_price, 2) }}</td>
                                        <td style="padding: 10px 14px; text-align:right; font-weight:600; color:#111827;">
                                            {{ $order->currency }} {{ number_format((float)$item->line_total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(!empty($order->pending_restock) && $order->pending_restock)
                        <div style="margin:16px 0; padding:12px 14px; border-radius:12px; border:1px solid #fcd34d; background:#fffbeb; font-size:14px; color:#92400e;">
                            <strong>Restock required</strong> — part of this order is not yet in stock. Fulfill restock requests linked to this order.
                            @if(!empty($order->partial_stock_summary))
                                <div style="margin-top:8px; color:#78350f;">{{ $order->partial_stock_summary }}</div>
                            @endif
                        </div>
                    @endif

                    <div style="background: linear-gradient(135deg,#eef2ff,#e0f2fe); border-radius:14px; padding:16px 16px 14px; border:1px solid #e2e8f0; margin:16px 0;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-size:13px; font-weight:600; color:#4b5563;">Payment Summary</span>
                            <span style="font-size:11px; color:#6366f1; text-transform:uppercase; letter-spacing:.06em;">INR</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:4px; color:#4b5563;">
                            <span>Subtotal</span>
                            <span>{{ $order->currency }} {{ number_format((float)$order->subtotal, 2) }}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:4px; color:#4b5563;">
                            <span>Shipping</span>
                            <span>
                                {{ $order->currency }} {{ number_format((float)$order->shipping_cost, 2) }}
                                @if($order->within_city)
                                    <span style="color:#16a34a; font-size:12px;">(within city)</span>
                                @endif
                            </span>
                        </div>
                        <div style="height:1px; background:linear-gradient(to right,transparent,#c4b5fd,#7dd3fc,transparent); margin:10px 0;"></div>
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:15px; font-weight:700; color:#111827;">
                            <span>Total</span>
                            <span>{{ $order->currency }} {{ number_format((float)$order->total, 2) }}</span>
                        </div>
                    </div>

                    @if($order->notes)
                        <div style="margin-top:16px; padding:12px 14px; border-radius:10px; border:1px dashed #e2e8f0; background:#f9fafb; font-size:14px;">
                            <div style="font-weight:600; color:#1f2937; margin-bottom:4px;">Customer Notes</div>
                            <div style="color:#4b5563;">{{ $order->notes }}</div>
                        </div>
                    @endif
                </div>

                <!-- Right: status (header), user, delivery and payment summary -->
                <div>
                    <div style="background: linear-gradient(135deg,#eff6ff,#fdf2ff); padding: 18px 18px 16px; border-radius: 14px; border:1px solid #e2e8f0; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                            <h3 style="margin:0; font-size:15px; font-weight:700; color: #1e293b;">Customer Information</h3>
                            <span style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.04em;">Customer</span>
                        </div>
                        @if($order->user)
                            <p style="margin-bottom:4px;"><strong>Name:</strong> {{ $order->user->name }}</p>
                            <p style="margin-bottom:4px;"><strong>Email:</strong> {{ $order->user->email ?? '—' }}</p>
                            <p style="margin-bottom:4px;"><strong>Phone:</strong> {{ $order->user->phone ?? '—' }}</p>
                            <p style="margin-bottom:0;"><strong>Country:</strong> {{ $order->user->country ?? '—' }}</p>
                        @else
                            <p style="margin:0; color:#64748b;">User not found.</p>
                        @endif
                    </div>

                    <div style="background: linear-gradient(135deg,#ecfeff,#f5f3ff); padding: 18px 18px 16px; border-radius: 14px; border:1px solid #e2e8f0; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                            <h3 style="margin:0; font-size:15px; font-weight:700; color: #1e293b;">Delivery Info</h3>
                            <span style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.04em;">Shipping</span>
                        </div>
                        <p style="margin-bottom:4px;"><span style="color:#64748b;">Requested date</span><br><strong>{{ $order->requested_delivery_date?->format('Y-m-d') ?? '—' }}</strong></p>
                        <p style="margin-bottom:4px;"><span style="color:#64748b;">Expected delivery</span><br><strong>{{ $order->expected_delivery_at ? $order->expected_delivery_at->format('Y-m-d H:i') : '—' }}</strong></p>
                        <p style="margin-bottom:8px;"><span style="color:#64748b;">Location</span><br><strong>{{ $order->delivery_location ?? '—' }}</strong></p>
                        @if($order->delivery_too_close)
                            <div style="margin-top:6px; padding:8px 10px; background:#fffbeb; border-radius:8px; border:1px solid #fbbf24; font-size:13px; color:#92400e;">
                                <strong>Delivery date too close</strong> — customer was shown "place a request" message.
                            </div>
                        @endif
                        @if($order->userAddress)
                            <div style="margin-top:10px; font-size:13px; color:#1f2937;">
                                <span style="color:#64748b;">Shipping address</span><br>
                                {{ $order->userAddress->address_line1 }}<br>
                                @if($order->userAddress->address_line2){{ $order->userAddress->address_line2 }}<br>@endif
                                {{ $order->userAddress->city }}, {{ $order->userAddress->state }} {{ $order->userAddress->pincode }}<br>
                                {{ $order->userAddress->country }}
                            </div>
                        @endif
                    </div>

                    {{-- status update is handled in header --}}
                </div>
            </div>
        </div>
    </div>
@endsection
