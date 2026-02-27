@extends('admin.layouts.app')
@section('title', 'Edit Location Distance')
@section('page-title', 'Edit Location Distance')

@section('content')
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h2 class="card-title">Edit distance</h2>
            <a href="{{ route('admin.location-distances.index') }}" style="color:#667eea; text-decoration:none;">Back to list</a>
        </div>
        @if($errors->any())
            <div style="background:#f8d7da; padding:12px; margin:20px; border-radius:6px;">
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form action="{{ route('admin.location-distances.update', $distance->id) }}" method="POST" style="padding:20px;">
            @csrf
            @method('PUT')
            <div style="margin-bottom:16px;">
                <label>From location</label>
                <input type="text" name="from_location" value="{{ old('from_location', $distance->from_location) }}" required style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
            </div>
            <div style="margin-bottom:16px;">
                <label>To location</label>
                <input type="text" name="to_location" value="{{ old('to_location', $distance->to_location) }}" required style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
            </div>
            <div style="margin-bottom:16px;">
                <label>Distance (km)</label>
                <input type="number" name="distance_km" value="{{ old('distance_km', $distance->distance_km) }}" step="0.01" min="0" required style="width:100%; max-width:200px; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
            </div>
            <div style="margin-bottom:16px;">
                <label>Notes (optional)</label>
                <textarea name="notes" rows="2" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">{{ old('notes', $distance->notes) }}</textarea>
            </div>
            <button type="submit" style="padding:10px 20px; background:#4299e1; color:white; border:none; border-radius:8px; cursor:pointer;">Update</button>
        </form>
    </div>
@endsection
