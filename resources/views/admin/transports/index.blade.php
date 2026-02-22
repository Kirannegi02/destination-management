@extends('admin.layouts.app')

@section('title', 'Transports Management')
@section('page-title', 'Transports Management')

@section('content')
    <style>
        .status-tab { padding:6px 12px; border-radius:4px; text-decoration:none; font-size:13px; }
        .status-tab--active { background:#667eea; color:white; }
        .status-tab--inactive { color:#4a5568; }
    </style>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                Transports
                <span style="font-size: 14px; font-weight: normal; color: #718096;">({{ $allCount }} total)</span>
            </h2>
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <a href="{{ route('admin.transports.create') }}"
                   style="padding:8px 16px; background:#48bb78; color:white; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500;">+ Add Transport</a>
                <a href="{{ route('admin.transports.import.form') }}"
                   style="padding:8px 14px; background:#4299e1; color:white; border-radius:6px; text-decoration:none; font-size:13px;">Bulk Import</a>
                <a href="{{ route('admin.transports.export.page') }}"
                   style="padding:8px 14px; background:#1e3a8a; color:white; border-radius:6px; text-decoration:none; font-size:13px;">Bulk Export</a>
                <div style="display:flex; gap:4px; background:#f7fafc; padding:4px; border-radius:6px;">
                    <a href="{{ route('admin.transports.index', ['status' => 'all']) }}"
                       class="status-tab {{ request('status','all') == 'all' ? 'status-tab--active' : 'status-tab--inactive' }}">All ({{ $allCount }})</a>
                    <a href="{{ route('admin.transports.index', ['status' => 'active']) }}"
                       class="status-tab {{ request('status') == 'active' ? 'status-tab--active' : 'status-tab--inactive' }}">Active ({{ $activeCount }})</a>
                    <a href="{{ route('admin.transports.index', ['status' => 'inactive']) }}"
                       class="status-tab {{ request('status') == 'inactive' ? 'status-tab--active' : 'status-tab--inactive' }}">Inactive ({{ $inactiveCount }})</a>
                    <a href="{{ route('admin.transports.index', ['status' => 'pending']) }}"
                       class="status-tab {{ request('status') == 'pending' ? 'status-tab--active' : 'status-tab--inactive' }}">Pending ({{ $pendingCount }})</a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div style="margin:16px; padding:12px; background:#c6f6d5; color:#22543d; border-radius:6px;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div style="margin:16px; padding:12px; background:#fed7d7; color:#742a2a; border-radius:6px;">{{ session('error') }}</div>
        @endif

        <div style="padding:16px; background:#f7fafc; border-bottom:1px solid #e2e8f0;">
            <form action="{{ route('admin.transports.index') }}" method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; margin-bottom:4px; font-size:12px; font-weight:600; color:#4a5568;">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Location, notes"
                           style="width:100%; padding:8px 12px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;">
                </div>
                <div style="flex:1; min-width:180px;">
                    <label style="display:block; margin-bottom:4px; font-size:12px; font-weight:600; color:#4a5568;">Vehicle</label>
                    <select name="vehicle_id" style="width:100%; padding:8px 12px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;">
                        <option value="">All Vehicles</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                <div style="display:flex; gap:8px;">
                    <button type="submit" style="padding:8px 16px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer; font-size:14px;">Filter</button>
                    @if(request('search') || request('vehicle_id'))
                        <a href="{{ route('admin.transports.index', ['status' => request('status', 'all')]) }}"
                           style="padding:8px 16px; background:#e2e8f0; color:#2d3748; border-radius:6px; text-decoration:none; font-size:14px;">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        @if($transports->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Location</th>
                        <th>Vehicle</th>
                        <th>Price/km</th>
                        <th>Price/day</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transports as $t)
                        <tr>
                            <td>{{ $t->id }}</td>
                            <td>{{ $t->location ?? '—' }}</td>
                            <td>{{ $t->vehicle ? $t->vehicle->name : 'N/A' }}</td>
                            <td>{{ number_format($t->price_per_km, 2) }}</td>
                            <td>{{ $t->price_per_day ? number_format($t->price_per_day, 2) : '—' }}</td>
                            <td>
                                @if($t->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($t->status === 'inactive')
                                    <span class="badge badge-danger">Inactive</span>
                                @else
                                    <span class="badge" style="background:#fbbf24; color:white;">Pending</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <a href="{{ route('admin.transports.show', $t->id) }}" style="padding:6px 12px; background:#48bb78; color:white; border-radius:6px; text-decoration:none; font-size:12px;">View</a>
                                    <a href="{{ route('admin.transports.edit', $t->id) }}" style="padding:6px 12px; background:#4299e1; color:white; border-radius:6px; text-decoration:none; font-size:12px;">Edit</a>
                                    <form action="{{ route('admin.transports.destroy', $t->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this transport?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="padding:6px 12px; background:#e53e3e; color:white; border:none; border-radius:6px; font-size:12px; cursor:pointer;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:20px; display:flex; justify-content:center;">{{ $transports->appends(request()->query())->links() }}</div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">Transport</div>
                <p>No transports found.</p>
                <a href="{{ route('admin.transports.create') }}" style="margin-top:16px; display:inline-block; background:#667eea; color:white; padding:10px 20px; border-radius:8px; text-decoration:none;">Add First Transport</a>
            </div>
        @endif
    </div>
@endsection
