@extends('admin.layouts.app')

@section('title', 'Souvenirs Management')
@section('page-title', 'Souvenirs Management')

@section('content')
    <style>
        .status-tab { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
        .status-tab--active { background: #667eea; color: white; }
        .status-tab--inactive { color: #4a5568; }
    </style>
    @php
        $exportFilters = array_filter([
            'status' => $status !== 'all' ? $status : null,
            'country' => request('country'),
        ]);
    @endphp
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                Souvenirs
                <span style="font-size: 14px; font-weight: normal; color: #718096;">({{ $allCount }} total)</span>
            </h2>
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('admin.souvenirs.create') }}"
                   style="padding: 8px 16px; background: #48bb78; color: white; border: none; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500;">
                    + Add Souvenir
                </a>
                <a href="{{ route('admin.souvenirs.export', array_merge($exportFilters, ['format' => 'xls'])) }}"
                   style="padding: 8px 14px; background: #1e3a8a; color: white; border-radius: 6px; text-decoration: none; font-size: 13px;">Export Excel</a>
                <a href="{{ route('admin.souvenirs.export', array_merge($exportFilters, ['format' => 'csv'])) }}"
                   style="padding: 8px 14px; background: #2b6cb0; color: white; border-radius: 6px; text-decoration: none; font-size: 13px;">Export CSV</a>
                <div style="display: flex; gap: 4px; background: #f7fafc; padding: 4px; border-radius: 6px;">
                    <a href="{{ route('admin.souvenirs.index', ['status' => 'all']) }}"
                       class="status-tab {{ $status == 'all' ? 'status-tab--active' : 'status-tab--inactive' }}">All ({{ $allCount }})</a>
                    <a href="{{ route('admin.souvenirs.index', ['status' => 'active']) }}"
                       class="status-tab {{ $status == 'active' ? 'status-tab--active' : 'status-tab--inactive' }}">Active ({{ $activeCount }})</a>
                    <a href="{{ route('admin.souvenirs.index', ['status' => 'inactive']) }}"
                       class="status-tab {{ $status == 'inactive' ? 'status-tab--active' : 'status-tab--inactive' }}">Inactive ({{ $inactiveCount }})</a>
                    <a href="{{ route('admin.souvenirs.index', ['status' => 'pending']) }}"
                       class="status-tab {{ $status == 'pending' ? 'status-tab--active' : 'status-tab--inactive' }}">Pending ({{ $pendingCount }})</a>
                </div>
            </div>
        </div>

        <div style="padding: 16px; background: #f7fafc; border-bottom: 1px solid #e2e8f0;">
            <form action="{{ route('admin.souvenirs.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
                <input type="hidden" name="status" value="{{ $status }}">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search souvenirs..."
                           style="width: 100%; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Country</label>
                    <select name="country" style="width: 100%; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Countries</option>
                        @foreach($countries as $c)
                            <option value="{{ $c }}" {{ request('country') == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">Filter</button>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Min Qty</th>
                        <th>Stock</th>
                        <th>City</th>
                        <th>Country</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($souvenirs as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td>
                                @if($s->images && count($s->images) > 0)
                                    <img src="{{ \App\Services\ImageService::getUrl($s->images[0]) }}" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                @else
                                    <span style="color: #a0aec0;">—</span>
                                @endif
                            </td>
                            <td>{{ $s->name }}</td>
                            <td>{{ $s->currency }} {{ number_format((float)$s->price, 2) }}</td>
                            <td>{{ $s->min_order_quantity }}</td>
                            <td>
                                @if($s->stock !== null && $s->stock <= 0)
                                    <span style="color: #e53e3e; font-weight: 600;">Out of stock</span>
                                @else
                                    {{ $s->stock ?? 0 }}
                                @endif
                            </td>
                            <td>{{ $s->city ?? '—' }}</td>
                            <td>{{ $s->country ?? '—' }}</td>
                            <td><span class="badge {{ $s->status_badge_class }}">{{ ucfirst($s->status) }}</span></td>
                            <td>
                                <a href="{{ route('admin.souvenirs.show', $s->id) }}" style="color: #4299e1; text-decoration: none; margin-right: 8px;">View</a>
                                <a href="{{ route('admin.souvenirs.edit', $s->id) }}" style="color: #667eea; text-decoration: none; margin-right: 8px;">Edit</a>
                                <form action="{{ route('admin.souvenirs.destroy', $s->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this souvenir?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="color: #e53e3e; background: none; border: none; cursor: pointer; padding: 0;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">No souvenirs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($souvenirs->hasPages())
            <div style="padding: 16px; border-top: 1px solid #e2e8f0;">
                {{ $souvenirs->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
