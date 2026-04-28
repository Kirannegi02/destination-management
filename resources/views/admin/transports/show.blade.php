@extends('admin.layouts.app')

@section('title', 'Transport Details')
@section('page-title', 'Transport Details')

@section('content')
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2 class="card-title">{{ $transport->location ?? '—' }}</h2>
                <div style="color:#4a5568; font-size:13px;">ID #{{ $transport->id }} | {{ ucfirst($transport->status) }}</div>
            </div>
            <a href="{{ route('admin.transports.index') }}" style="color:#667eea; text-decoration:none; font-size:14px;">Back to Transports</a>
        </div>

        <div style="padding:20px; display:grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap:20px;">
            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Zone</h4>
                <p style="color:#4a5568;">
                    @if($transport->zone)
                        <strong>{{ $transport->zone->name }}</strong>
                        <span style="display:block; font-size:13px; margin-top:6px;">Cities: {{ implode(', ', $transport->zone->cities ?? []) }}</span>
                        @if($transport->zone->price_per_day !== null && $transport->zone->price_per_day !== '')
                            <span style="display:block; font-size:13px; margin-top:6px;">
                                Zone price per day: <strong>{{ number_format((float) $transport->zone->price_per_day, 2) }} {{ $transport->zone->currency ?? '' }}</strong>
                            </span>
                        @endif
                    @else
                        <span>—</span>
                    @endif
                </p>
            </div>
            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Location label</h4>
                <p style="color:#4a5568;"><strong>{{ $transport->location ?? '—' }}</strong></p>
            </div>
            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Vehicle</h4>
                <p style="color:#4a5568;">Type: <strong>{{ $transport->vehicle ? $transport->vehicle->name : 'N/A' }}</strong></p>
                @if($transport->vehicle && $transport->vehicle->capacity_seats)
                    <p style="color:#4a5568;">Capacity: <strong>{{ $transport->vehicle->capacity_seats }} seats</strong></p>
                @endif
            </div>
            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Daily hire (zone vs row)</h4>
                <p style="color:#4a5568;">
                    Zone daily rate (used for quotes when set):
                    <strong>
                        @if($transport->zone && ($transport->zone->price_per_day !== null && $transport->zone->price_per_day !== ''))
                            {{ number_format((float) $transport->zone->price_per_day, 2) }} {{ $transport->zone->currency ?? '' }}
                        @else
                            —
                        @endif
                    </strong>
                </p>
                <p style="color:#4a5568; font-size:13px;">
                    Row fallback (legacy):
                    <strong>{{ $transport->price_per_day ? number_format($transport->price_per_day, 2) . ' ' . ($transport->currency ?? '') : '—' }}</strong>
                </p>
            </div>
            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Distance-based (between cities)</h4>
                <p style="color:#4a5568;">Price per km: <strong>{{ number_format($transport->price_per_km, 2) }}</strong></p>
            </div>
        </div>

        @if($transport->notes)
            <div style="padding:20px;">
                <h4 style="color:#2d3748; margin-bottom:8px;">Notes</h4>
                <p style="color:#4a5568; white-space:pre-wrap;">{{ $transport->notes }}</p>
            </div>
        @endif

        <div style="padding:20px; border-top:1px solid #e2e8f0;">
            <a href="{{ route('admin.transports.edit', $transport->id) }}" style="padding:8px 16px; background:#4299e1; color:white; border-radius:6px; text-decoration:none; font-size:14px;">Edit</a>
            <a href="{{ route('admin.transports.index') }}" style="padding:8px 16px; background:#e2e8f0; color:#2d3748; border-radius:6px; text-decoration:none; font-size:14px; margin-left:8px;">Back to list</a>
        </div>
    </div>
@endsection
