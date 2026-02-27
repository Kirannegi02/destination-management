@extends('admin.layouts.app')
@section('title', 'Quote Request #' . $booking->id)
@section('page-title', 'Quote Request #' . $booking->id)

@section('content')
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
            <div>
                <h2 class="card-title">Quote Request #{{ $booking->id }}</h2>
                <p style="color:#718096; font-size:14px;">{{ $booking->created_at?->format('d M Y H:i') }} | @if($booking->status === 'pending')Received@elseif($booking->status === 'confirmed')Quotation Sent@else{{ ucfirst($booking->status) }}@endif</p>
            </div>
            <a href="{{ route('admin.transport-bookings.index') }}" style="color:#667eea; text-decoration:none;">← Back to list</a>
        </div>
        @if(session('success'))
            <div style="margin:16px; padding:12px; background:#c6f6d5; color:#22543d; border-radius:6px;">{{ session('success') }}</div>
        @endif

        <div style="padding:20px;">
            <h3 style="color:#2d3748; margin-bottom:12px; border-bottom:2px solid #e2e8f0; padding-bottom:8px;">Submitted query – contact</h3>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
                <div style="background:#f7fafc; padding:16px; border-radius:8px;">
                    <p><strong>Name</strong> {{ $booking->guest_name ?? $booking->user?->name ?? '—' }}</p>
                    <p style="color:#4a5568;"><strong>Email</strong> {{ $booking->guest_email ?? $booking->user?->email ?? '—' }}</p>
                    <p style="color:#4a5568;"><strong>Phone</strong> {{ $booking->guest_phone ?? $booking->user?->phone ?? '—' }}</p>
                    @if($booking->guest_country)<p style="color:#4a5568;"><strong>Country</strong> {{ $booking->guest_country }}</p>@endif
                    <p style="color:#718096; font-size:13px; margin-top:12px;"><strong>Submitted at</strong> {{ $booking->created_at?->format('d M Y, H:i') }}</p>
                </div>
                <div style="background:#f7fafc; padding:16px; border-radius:8px;">
                    <p><strong>Trip type</strong> {{ ucfirst(str_replace('_',' ', $booking->trip_type)) }}</p>
                    <p style="color:#4a5568;"><strong>Passengers</strong> {{ $booking->passengers }}</p>
                    <p style="color:#4a5568;"><strong>Cities / route</strong> {{ implode(' → ', $booking->cities ?? []) }}</p>
                    <p style="color:#4a5568;"><strong>Days per city</strong> {{ implode(', ', $booking->days_per_city ?? []) }}</p>
                    @if($booking->vehicle)
                        <p style="margin-top:8px;"><strong>Vehicle (suggested)</strong> {{ $booking->vehicle->name }} ({{ $booking->vehicle->capacity_seats }} seats)</p>
                    @endif
                </div>
            </div>
        </div>

        @php $legsByTrain = $booking->legs_by_train ?? []; $cities = $booking->cities ?? []; @endphp
        @if(count($legsByTrain) > 0 && count($cities) > 1)
            <div style="padding:20px; background:#f0fff4; border-top:1px solid #e2e8f0;">
                <h4 style="color:#2d3748; margin-bottom:8px;">Travel between cities</h4>
                <p style="color:#4a5568; font-size:14px;">For each leg: <strong>By your vehicle</strong> = distance charge; <strong>By other vehicle</strong> (e.g. train) = within-city transfers only.</p>
                <ul style="color:#4a5568; margin-top:8px; padding-left:20px;">
                    @for($i = 0; $i < count($legsByTrain) && isset($cities[$i + 1]); $i++)
                        <li>Leg {{ $i + 1 }} ({{ $cities[$i] }} → {{ $cities[$i + 1] }}): {{ !empty($legsByTrain[$i]) ? 'By other vehicle (e.g. train)' : 'By your vehicle' }}</li>
                    @endfor
                </ul>
            </div>
        @endif

        @if($booking->remarks)
            <div style="padding:20px;">
                <h4 style="color:#2d3748; margin-bottom:8px;">Remarks / special requests</h4>
                <p style="color:#4a5568; white-space:pre-wrap;">{{ $booking->remarks }}</p>
            </div>
        @endif

        @php $breakdown = $booking->quote_breakdown ?? []; $lineItems = $breakdown['line_items'] ?? []; @endphp
        @if(count($lineItems) > 0)
            <div style="padding:20px;">
                <h4 style="color:#2d3748; margin-bottom:12px;">Quotation (calculated from query)</h4>
                <table class="table" style="font-size:14px;">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>City / Route</th>
                            <th>Description</th>
                            <th>Vehicle</th>
                            <th style="text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lineItems as $row)
                            <tr>
                                <td>{{ $row['day'] ?? '—' }}</td>
                                <td>{{ $row['city'] ?? '—' }}</td>
                                <td>{{ $row['description'] ?? '—' }}</td>
                                <td>{{ $row['vehicle_display'] ?? '—' }}</td>
                                <td style="text-align:right;">{{ isset($row['currency']) ? $row['currency'] . ' ' : '' }}{{ isset($row['amount']) ? number_format($row['amount'], 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p style="margin-top:12px; font-weight:600;">Total: {{ $booking->currency ?? '' }} {{ $booking->total_amount ? number_format((float)$booking->total_amount, 2) : '—' }}</p>
            </div>
        @endif

        @if($booking->status !== 'cancelled')
            <div style="padding:20px; border-top:1px solid #e2e8f0;">
                <h4 style="margin-bottom:12px;">Update status</h4>
                <form action="{{ route('admin.transport-bookings.status', $booking->id) }}" method="POST" style="display:flex; gap:8px; flex-wrap:wrap;">
                    @csrf
                    <select name="status" style="padding:8px 12px; border:2px solid #e2e8f0; border-radius:6px;">
                        <option value="pending" {{ $booking->status === 'pending' ? 'selected' : '' }}>Received</option>
                        <option value="confirmed" {{ $booking->status === 'confirmed' ? 'selected' : '' }}>Quotation Sent</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button type="submit" style="padding:8px 16px; background:#4299e1; color:white; border:none; border-radius:6px; cursor:pointer;">Update</button>
                </form>
            </div>
        @endif
    </div>
@endsection
