@extends('admin.layouts.app')
@section('title', 'Edit Vehicle')
@section('page-title', 'Edit Vehicle')
@section('content')
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h2 class="card-title">Edit Vehicle</h2>
        <a href="{{ route('admin.vehicles.index') }}" style="color:#667eea;text-decoration:none;">Back to Vehicles</a>
    </div>
    @if($errors->any())
    <div style="background:#f8d7da;padding:12px;margin:20px;border-radius:6px;"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form action="{{ route('admin.vehicles.update', $vehicle->id) }}" method="POST" style="padding:20px;">
        @csrf
        @method('PUT')
        <div style="margin-bottom:20px;"><label>Name (required)</label><input type="text" name="name" value="{{ old('name', $vehicle->name) }}" required style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div style="margin-bottom:20px;"><label>Capacity (seats)</label><input type="number" name="capacity_seats" value="{{ old('capacity_seats', $vehicle->capacity_seats) }}" min="1" max="100" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div style="margin-bottom:20px;"><label>Description</label><textarea name="description" rows="3" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">{{ old('description', $vehicle->description) }}</textarea></div>
        <div style="margin-bottom:20px;"><label>Default price per km</label><input type="number" name="default_price_per_km" value="{{ old('default_price_per_km', $vehicle->default_price_per_km) }}" step="0.01" min="0" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div style="margin-bottom:20px;"><label>Currency</label><input type="text" name="currency" value="{{ old('currency', $vehicle->currency ?? 'INR') }}" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div style="margin-bottom:20px;"><label>Status (required)</label><select name="status" required style="width:100%;max-width:200px;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"><option value="active" {{ old('status',$vehicle->status)=='active'?'selected':'' }}>Active</option><option value="inactive" {{ old('status',$vehicle->status)=='inactive'?'selected':'' }}>Inactive</option><option value="pending" {{ old('status',$vehicle->status)=='pending'?'selected':'' }}>Pending</option></select></div>
        <div style="margin-bottom:20px;"><label>Sort order</label><input type="number" name="sort_order" value="{{ old('sort_order', $vehicle->sort_order) }}" min="0" style="width:100%;max-width:120px;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <button type="submit" style="padding:10px 20px;background:#4299e1;color:white;border:none;border-radius:8px;cursor:pointer;">Update Vehicle</button>
    </form>
</div>
@endsection
