@extends('admin.layouts.app')

@section('title', 'Guides Management')
@section('page-title', 'Guides Management')

@section('content')
    <style>
        .status-tab { padding:6px 12px; border-radius:4px; text-decoration:none; font-size:13px; }
        .status-tab--active { background:#667eea; color:white; }
        .status-tab--inactive { color:#4a5568; }
    </style>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                Guides
                <span style="font-size: 14px; font-weight: normal; color: #718096;">
                    ({{ $allCount }} total)
                </span>
            </h2>
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <a href="{{ route('admin.guides.create') }}"
                   style="padding:8px 16px; background:#48bb78; color:white; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; display:inline-flex; align-items:center; gap:6px;">
                    <span>+</span> Add Guide
                </a>
                <div style="display:flex; gap:6px;">
                    <a href="{{ route('admin.guides.export', ['format' => 'xls']) }}"
                       style="padding:8px 14px; background:#1e3a8a; color:white; border-radius:6px; text-decoration:none; font-size:13px;">
                        Export Excel
                    </a>
                    <a href="{{ route('admin.guides.export', ['format' => 'csv']) }}"
                       style="padding:8px 14px; background:#2b6cb0; color:white; border-radius:6px; text-decoration:none; font-size:13px;">
                        Export CSV
                    </a>
                </div>
                <div style="display:flex; gap:4px; background:#f7fafc; padding:4px; border-radius:6px;">
                    <a href="{{ route('admin.guides.index', ['status' => 'all']) }}"
                       class="status-tab {{ request('status','all') == 'all' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        All ({{ $allCount }})
                    </a>
                    <a href="{{ route('admin.guides.index', ['status' => 'active']) }}"
                       class="status-tab {{ request('status') == 'active' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        Active ({{ $activeCount }})
                    </a>
                    <a href="{{ route('admin.guides.index', ['status' => 'inactive']) }}"
                       class="status-tab {{ request('status') == 'inactive' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        Inactive ({{ $inactiveCount }})
                    </a>
                    <a href="{{ route('admin.guides.index', ['status' => 'pending']) }}"
                       class="status-tab {{ request('status') == 'pending' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        Pending ({{ $pendingCount }})
                    </a>
                </div>
            </div>
        </div>

        <div style="padding:16px; background:#f7fafc; border-bottom:1px solid #e2e8f0;">
            <form action="{{ route('admin.guides.index') }}" method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; margin-bottom:4px; font-size:12px; font-weight:600; color:#4a5568;">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search guide title, city, language"
                           style="width:100%; padding:8px 12px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;">
                </div>
                <div style="flex:1; min-width:150px;">
                    <label style="display:block; margin-bottom:4px; font-size:12px; font-weight:600; color:#4a5568;">City</label>
                    <select name="city" style="width:100%; padding:8px 12px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;">
                        <option value="">All Cities</option>
                        @foreach($cities as $city)
                            <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1; min-width:150px;">
                    <label style="display:block; margin-bottom:4px; font-size:12px; font-weight:600; color:#4a5568;">Language</label>
                    <select name="language" style="width:100%; padding:8px 12px; border:2px solid #e2e8f0; border-radius:6px; font-size:14px;">
                        <option value="">All Languages</option>
                        @foreach($languages as $language)
                            <option value="{{ $language }}" {{ request('language') == $language ? 'selected' : '' }}>{{ $language }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit"
                            style="padding:8px 16px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer; font-size:14px;">
                        Filter
                    </button>
                    @if(request('search') || request('city') || request('language') || request('status'))
                        <a href="{{ route('admin.guides.index') }}"
                           style="padding:8px 16px; background:#e2e8f0; color:#2d3748; border-radius:6px; text-decoration:none; font-size:14px; display:inline-block;">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @if($guides->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>City / Country</th>
                        <th>Language</th>
                        <th>Service Date</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($guides as $guide)
                        <tr>
                            <td>{{ $guide->id }}</td>
                            <td>
                                <strong>{{ $guide->title }}</strong>
                                @if($guide->price)
                                    <br><small style="color:#667eea;">₹{{ number_format($guide->price, 2) }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $guide->city ?? 'N/A' }}
                                @if($guide->country)
                                    , {{ $guide->country }}
                                @endif
                            </td>
                            <td>{{ $guide->language ?? 'N/A' }}</td>
                            <td>{{ $guide->service_date ? $guide->service_date->format('d M Y') : 'Flexible' }}</td>
                            <td>
                                @if($guide->duration_hours)
                                    {{ $guide->duration_hours }} hrs
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($guide->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($guide->status === 'inactive')
                                    <span class="badge badge-danger">Inactive</span>
                                @else
                                    <span class="badge" style="background:#fbbf24; color:white;">Pending</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <a href="{{ route('admin.guides.show', $guide->id) }}"
                                       style="padding:6px 12px; background:#48bb78; color:white; border-radius:6px; text-decoration:none; font-size:12px;">View</a>
                                    <a href="{{ route('admin.guides.edit', $guide->id) }}"
                                       style="padding:6px 12px; background:#4299e1; color:white; border-radius:6px; text-decoration:none; font-size:12px;">Edit</a>
                                    <form action="{{ route('admin.guides.destroy', $guide->id) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete this guide?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                style="padding:6px 12px; background:#e53e3e; color:white; border:none; border-radius:6px; font-size:12px; cursor:pointer;">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:20px; display:flex; justify-content:center;">
                {{ $guides->appends(request()->query())->links() }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">👨‍🏫</div>
                <p>No guides found.</p>
                <a href="{{ route('admin.guides.create') }}"
                   style="margin-top:16px; display:inline-block; background:#667eea; color:white; padding:10px 20px; border-radius:8px; text-decoration:none;">
                    Create First Guide
                </a>
            </div>
        @endif
    </div>
@endsection


