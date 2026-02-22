@extends('admin.layouts.app')
@section('title', 'Add Location Distance')
@section('page-title', 'Add Location Distance')

@section('content')
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h2 class="card-title">Add distance between two locations</h2>
            <a href="{{ route('admin.location-distances.index') }}" style="color:#667eea; text-decoration:none;">Back to list</a>
        </div>
        @if($errors->any())
            <div style="background:#f8d7da; padding:12px; margin:20px; border-radius:6px;">
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form action="{{ route('admin.location-distances.store') }}" method="POST" style="padding:20px;">
            @csrf
            <div style="margin-bottom:16px;">
                <label>From location (city name)</label>
                <input type="text" name="from_location" value="{{ old('from_location') }}" placeholder="e.g. Dehradun" required style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
            </div>
            <div style="margin-bottom:16px;">
                <label>To location (city name)</label>
                <input type="text" name="to_location" value="{{ old('to_location') }}" placeholder="e.g. Mumbai" required style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
            </div>
            <div style="margin-bottom:16px;">
                <label>Distance (km)</label>
                <input type="number" name="distance_km" value="{{ old('distance_km') }}" step="0.01" min="0" required style="width:100%; max-width:200px; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
            </div>
            <div style="margin-bottom:16px;">
                <label>Notes (optional)</label>
                <textarea name="notes" rows="2" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" style="padding:10px 20px; background:#48bb78; color:white; border:none; border-radius:8px; cursor:pointer;">Add distance</button>
        </form>
    </div>
@endsection
