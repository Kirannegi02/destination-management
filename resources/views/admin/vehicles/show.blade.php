@extends('admin.layouts.app')
@section('title', 'Vehicle Details')
@section('page-title', 'Vehicle Details')
@section('content')
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <div><h2 class="card-title">{{ $vehicle->name }}</h2><div style="color:#4a5568;font-size:13px;">ID #{{ $vehicle->id }} | {{ ucfirst($vehicle->status) }}</div></div>
        <a href="{{ route('admin.vehicles.index') }}" style="color:#667eea;text-decoration:none;">Back to Vehicles</a>
    </div>
    <div style="padding:20px;">
        @if($vehicle->image_url)
            <div style="margin-bottom:20px;">
                <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->name }}" style="max-width:280px;max-height:200px;object-fit:contain;border:1px solid #e2e8f0;border-radius:8px;">
            </div>
        @endif
        <p><strong>Category:</strong> {{ $vehicle->vehicle_category ? \Illuminate\Support\Arr::get(\App\Models\Vehicle::CATEGORIES, $vehicle->vehicle_category, $vehicle->vehicle_category) : '—' }}</p>
        <p><strong>Capacity:</strong> {{ $vehicle->capacity_seats ?? 'N/A' }} seats</p>
        @if($vehicle->description)<p><strong>Description:</strong> {{ $vehicle->description }}</p>@endif
        <p><strong>Display order:</strong> {{ $vehicle->sort_order ?? 0 }}</p>
        <p><strong>Transport routes:</strong> {{ $vehicle->transports->count() }}</p>
    </div>
    <div style="padding:20px;border-top:1px solid #e2e8f0;">
        <a href="{{ route('admin.vehicles.edit', $vehicle->id) }}" style="padding:8px 16px;background:#4299e1;color:white;border-radius:6px;text-decoration:none;">Edit</a>
        <a href="{{ route('admin.vehicles.index') }}" style="padding:8px 16px;background:#e2e8f0;color:#2d3748;border-radius:6px;text-decoration:none;margin-left:8px;">Back to list</a>
    </div>
</div>
@endsection
