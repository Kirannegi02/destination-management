@extends('admin.layouts.app')
@section('title', 'Quote Requests')
@section('page-title', 'Quote Requests')

@section('content')
    <style>
        .status-tab { padding:6px 12px; border-radius:4px; text-decoration:none; font-size:13px; }
        .status-tab--active { background:#667eea; color:white; }
        .status-tab--inactive { color:#4a5568; }
    </style>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Submitted queries (quote requests)</h2>
            <p style="color:#4a5568; font-size:14px; margin-top:4px;">All details and quotations from user-submitted queries are shown here. Click View to see full query and quotation.</p>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <div style="display:flex; gap:4px; background:#f7fafc; padding:4px; border-radius:6px;">
                    <a href="{{ route('admin.transport-bookings.index', ['status' => 'all']) }}" class="status-tab {{ request('status','all') == 'all' ? 'status-tab--active' : 'status-tab--inactive' }}">All ({{ $counts['all'] }})</a>
                    <a href="{{ route('admin.transport-bookings.index', ['status' => 'pending']) }}" class="status-tab {{ request('status') == 'pending' ? 'status-tab--active' : 'status-tab--inactive' }}">Received ({{ $counts['pending'] }})</a>
                    <a href="{{ route('admin.transport-bookings.index', ['status' => 'confirmed']) }}" class="status-tab {{ request('status') == 'confirmed' ? 'status-tab--active' : 'status-tab--inactive' }}">Quotation Sent ({{ $counts['confirmed'] }})</a>
                    <a href="{{ route('admin.transport-bookings.index', ['status' => 'cancelled']) }}" class="status-tab {{ request('status') == 'cancelled' ? 'status-tab--active' : 'status-tab--inactive' }}">Cancelled ({{ $counts['cancelled'] }})</a>
                </div>
            </div>
        </div>
        @if(session('success'))
            <div style="margin:16px; padding:12px; background:#c6f6d5; color:#22543d; border-radius:6px;">{{ session('success') }}</div>
        @endif
        <div style="padding:16px; background:#f7fafc; border-bottom:1px solid #e2e8f0;">
            <form action="{{ route('admin.transport-bookings.index') }}" method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
                <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; margin-bottom:4px; font-size:12px; font-weight:600; color:#4a5568;">Search (name, email, phone, ID)</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search" style="width:100%; padding:8px 12px; border:2px solid #e2e8f0; border-radius:6px;">
                </div>
                <button type="submit" style="padding:8px 16px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer;">Search</button>
            </form>
        </div>
        @if($bookings->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contact</th>
                        <th>Query (trip)</th>
                        <th>Vehicle</th>
                        <th>Quoted total</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $b)
                        <tr>
                            <td>{{ $b->id }}</td>
                            <td>{{ $b->guest_name ?? $b->user?->name }}<br><small>{{ $b->guest_email ?? $b->user?->email }}</small></td>
                            <td>{{ ucfirst(str_replace('_',' ', $b->trip_type)) }}<br><small>{{ implode(' → ', $b->cities ?? []) }}</small></td>
                            <td>{{ $b->vehicle?->name ?? '—' }} ({{ $b->passengers }} pax)</td>
                            <td>{{ $b->currency }} {{ $b->total_amount ? number_format($b->total_amount, 2) : '—' }}</td>
                            <td>
                                @if($b->status === 'pending')<span class="badge" style="background:#f59e0b; color:white;">Received</span>
                                @elseif($b->status === 'confirmed')<span class="badge badge-success">Quotation Sent</span>
                                @elseif($b->status === 'cancelled')<span class="badge badge-danger">Cancelled</span>
                                @else<span class="badge" style="background:#94a3b8;">Quote</span>@endif
                            </td>
                            <td>{{ $b->created_at?->format('d M Y H:i') }}</td>
                            <td><a href="{{ route('admin.transport-bookings.show', $b->id) }}" style="padding:6px 12px; background:#48bb78; color:white; border-radius:6px; text-decoration:none; font-size:12px;">View full query & quotation</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:20px;">{{ $bookings->appends(request()->query())->links() }}</div>
        @else
            <div class="empty-state" style="padding:40px; text-align:center;">
                <p>No submitted queries yet. When users submit a quote request via the API or app, their query and quotation will appear here.</p>
            </div>
        @endif
    </div>
@endsection
