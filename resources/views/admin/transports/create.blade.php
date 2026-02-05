@extends('admin.layouts.app')
@section('title', 'Add Transport')
@section('page-title', 'Add Transport')
@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="card-title">Add Transport</h2>
        <a href="{{ route('admin.transports.index') }}" style="color:#667eea; text-decoration:none;">Back to Transports</a>
    </div>
    @if($errors->any())
    <div style="background:#f8d7da; padding:12px; margin:20px; border-radius:6px;">
        <strong>Errors:</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif
    <form action="{{ route('admin.transports.store') }}" method="POST" style="padding:20px;">
        @csrf
        <div style="margin-bottom:20px;">
            <label>Location (e.g. city, country)</label>
            <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Delhi, India" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
        </div>
        <div style="margin-bottom:20px;">
            <label>Vehicle (required)</label>
            <select name="vehicle_id" required style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                <option value="">Select vehicle</option>
                @foreach($vehicles as $v)
                <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}@if($v->capacity_seats) ({{ $v->capacity_seats }} seats)@endif</option>
                @endforeach
            </select>
            @if($vehicles->isEmpty())
            <small style="color:#e53e3e;">Add a vehicle first from Transport menu.</small>
            @endif
        </div>
        <div style="margin-bottom:20px;">
            <label>Price per km (required)</label>
            <input type="number" name="price_per_km" value="{{ old('price_per_km') }}" step="0.01" min="0" required style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
        </div>
        <div style="margin-bottom:20px;">
            <label>Minimum charge</label>
            <input type="number" name="min_charge" value="{{ old('min_charge') }}" step="0.01" min="0" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
        </div>
        <div style="margin-bottom:20px;">
            <label>Notes</label>
            <textarea name="notes" rows="3" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">{{ old('notes') }}</textarea>
        </div>
        <div style="margin-bottom:20px;">
            <label>Status (required)</label>
            <select name="status" required style="width:100%; max-width:200px; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                <option value="active" {{ old('status','active') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
        </div>
        <button type="submit" style="padding:10px 20px; background:#48bb78; color:white; border:none; border-radius:8px; cursor:pointer;">Create Transport</button>
    </form>
</div>
@endsection
