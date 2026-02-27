@extends('admin.layouts.app')
@section('title', 'Location Distances')
@section('page-title', 'Location Distances')

@section('content')
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
            <h2 class="card-title">Distances between locations (for quote calculation)</h2>
            <a href="{{ route('admin.location-distances.create') }}" style="padding:8px 16px; background:#48bb78; color:white; border-radius:6px; text-decoration:none; font-size:14px;">+ Add Distance</a>
        </div>
        @if(session('success'))
            <div style="margin:16px; padding:12px; background:#c6f6d5; color:#22543d; border-radius:6px;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div style="margin:16px; padding:12px; background:#fed7d7; color:#742a2a; border-radius:6px;">{{ session('error') }}</div>
        @endif
        <div style="padding:16px; background:#f7fafc; border-bottom:1px solid #e2e8f0;">
            <form action="{{ route('admin.location-distances.index') }}" method="GET" style="display:flex; gap:12px; align-items:end;">
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; margin-bottom:4px; font-size:12px; font-weight:600; color:#4a5568;">Search (from/to)</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="City or location" style="width:100%; padding:8px 12px; border:2px solid #e2e8f0; border-radius:6px;">
                </div>
                <button type="submit" style="padding:8px 16px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer;">Search</button>
            </form>
        </div>
        @if($distances->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>To</th>
                        <th>Distance (km)</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distances as $d)
                        <tr>
                            <td>{{ $d->from_location }}</td>
                            <td>{{ $d->to_location }}</td>
                            <td>{{ number_format($d->distance_km, 2) }}</td>
                            <td>{{ Str::limit($d->notes, 40) }}</td>
                            <td>
                                <a href="{{ route('admin.location-distances.edit', $d->id) }}" style="padding:6px 12px; background:#4299e1; color:white; border-radius:6px; text-decoration:none; font-size:12px;">Edit</a>
                                <form action="{{ route('admin.location-distances.destroy', $d->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove this distance?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="padding:6px 12px; background:#e53e3e; color:white; border:none; border-radius:6px; font-size:12px; cursor:pointer;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:20px;">{{ $distances->appends(request()->query())->links() }}</div>
        @else
            <div class="empty-state" style="padding:40px; text-align:center;">
                <p>No location distances yet. Add distances (e.g. Dehradun to Mumbai) so quotes can calculate per-km charges.</p>
                <a href="{{ route('admin.location-distances.create') }}" style="margin-top:16px; display:inline-block; background:#667eea; color:white; padding:10px 20px; border-radius:8px; text-decoration:none;">+ Add first distance</a>
            </div>
        @endif
    </div>
@endsection
