@extends('admin.layouts.app')

@section('title', 'Private Venues')
@section('page-title', 'Private Venues')

@section('content')
    <style>
        .status-tab { padding:6px 12px; border-radius:4px; text-decoration:none; font-size:13px; }
        .status-tab--active { background:#667eea; color:white; }
        .status-tab--inactive { color:#4a5568; }
    </style>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                Private Venues
                <span style="font-size:14px;font-weight:normal;color:#718096;">({{ $allCount }} total)</span>
            </h2>
            <a href="{{ route('admin.private-venues.create') }}"
               style="background:#667eea;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:500;">
                + Add Venue
            </a>
        </div>

        <div style="padding:16px;background:#f7fafc;border-bottom:1px solid #e2e8f0;">
            <form method="GET" action="{{ route('admin.private-venues.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;">
                <input type="hidden" name="status" value="{{ request('status','all') }}">
                <div style="flex:1;min-width:200px;">
                    <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, city, brand..."
                           style="width:100%;padding:8px 12px;border:2px solid #e2e8f0;border-radius:6px;">
                </div>
                <div style="min-width:140px;">
                    <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;">City</label>
                    <select name="city" style="width:100%;padding:8px;border:2px solid #e2e8f0;border-radius:6px;">
                        <option value="">All</option>
                        @foreach($cities as $c)
                            <option value="{{ $c }}" {{ request('city') == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width:160px;">
                    <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;">Venue type</label>
                    <select name="venue_type" style="width:100%;padding:8px;border:2px solid #e2e8f0;border-radius:6px;">
                        <option value="">All types</option>
                        @foreach(\App\Models\PrivateVenue::venueTypes() as $key => $label)
                            <option value="{{ $key }}" {{ request('venue_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" style="padding:8px 16px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;">Filter</button>
            </form>
            <div style="display:flex;gap:4px;margin-top:12px;background:#fff;padding:4px;border-radius:6px;width:fit-content;">
                <a href="{{ route('admin.private-venues.index', ['status' => 'all']) }}" class="status-tab {{ request('status','all') == 'all' ? 'status-tab--active' : 'status-tab--inactive' }}">All ({{ $allCount }})</a>
                <a href="{{ route('admin.private-venues.index', ['status' => 'active']) }}" class="status-tab {{ request('status') == 'active' ? 'status-tab--active' : 'status-tab--inactive' }}">Active ({{ $activeCount }})</a>
                <a href="{{ route('admin.private-venues.index', ['status' => 'pending']) }}" class="status-tab {{ request('status') == 'pending' ? 'status-tab--active' : 'status-tab--inactive' }}">Pending ({{ $pendingCount }})</a>
                <a href="{{ route('admin.private-venues.index', ['status' => 'inactive']) }}" class="status-tab {{ request('status') == 'inactive' ? 'status-tab--active' : 'status-tab--inactive' }}">Inactive ({{ $inactiveCount }})</a>
            </div>
        </div>

        @if(session('success'))
            <div style="margin:16px;padding:12px;background:#d4edda;color:#155724;border-radius:6px;">{{ session('success') }}</div>
        @endif

        @if($venues->count())
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Venue</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Event size</th>
                        <th>Meeting rooms</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venues as $v)
                        <tr>
                            <td>{{ $v->id }}</td>
                            <td>
                                <strong>{{ $v->name }}</strong>
                                @if($v->is_featured)<span class="badge" style="background:#f6e05e;color:#744210;margin-left:6px;">Featured</span>@endif
                                @if($v->brand_chain)<br><small style="color:#718096;">{{ $v->brand_chain }}</small>@endif
                            </td>
                            <td>{{ $v->venue_type_label }}</td>
                            <td>{{ $v->location_label ?: '—' }}</td>
                            <td>
                                @if($v->min_event_size || $v->max_event_size)
                                    {{ $v->min_event_size ?? '?' }} – {{ $v->max_event_size ?? '?' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $v->spaces_count }} space(s)</td>
                            <td>
                                <span class="badge {{ $v->status === 'active' ? 'badge-success' : ($v->status === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ ucfirst($v->status) }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="{{ route('admin.private-venues.show', $v) }}" style="padding:6px 10px;background:#48bb78;color:white;border-radius:6px;text-decoration:none;font-size:12px;">View</a>
                                    <a href="{{ route('admin.private-venues.edit', $v) }}" style="padding:6px 10px;background:#4299e1;color:white;border-radius:6px;text-decoration:none;font-size:12px;">Edit</a>
                                    <form action="{{ route('admin.private-venues.destroy', $v) }}" method="POST" onsubmit="return confirm('Delete this venue and all meeting spaces?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="padding:6px 10px;background:#e53e3e;color:white;border:none;border-radius:6px;font-size:12px;cursor:pointer;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin:20px;display:flex;justify-content:center;">{{ $venues->withQueryString()->links() }}</div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">🏰</div>
                <p>No private venues yet. Add your first event venue profile (hotel, conference center, unique space).</p>
                <a href="{{ route('admin.private-venues.create') }}" style="margin-top:16px;display:inline-block;background:#667eea;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;">Add Venue</a>
            </div>
        @endif
    </div>
@endsection
