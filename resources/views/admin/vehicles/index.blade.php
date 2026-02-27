@extends('admin.layouts.app')
@section('title', 'Vehicles Management')
@section('page-title', 'Vehicles Management')
@section('content')
<style>.status-tab{padding:6px 12px;border-radius:4px;text-decoration:none;font-size:13px;}.status-tab--active{background:#667eea;color:white;}.status-tab--inactive{color:#4a5568;}</style>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Vehicles <span style="font-size:14px;font-weight:normal;color:#718096;">({{ $allCount }} total)</span></h2>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.vehicles.create') }}" style="padding:8px 16px;background:#48bb78;color:white;border-radius:6px;text-decoration:none;font-size:14px;">+ Add Vehicle</a>
            <a href="{{ route('admin.vehicles.import.form') }}" style="padding:8px 14px;background:#4299e1;color:white;border-radius:6px;text-decoration:none;font-size:13px;">Bulk Import</a>
            <a href="{{ route('admin.vehicles.export.page') }}" style="padding:8px 14px;background:#1e3a8a;color:white;border-radius:6px;text-decoration:none;font-size:13px;">Bulk Export</a>
            <div style="display:flex;gap:4px;background:#f7fafc;padding:4px;border-radius:6px;">
                <a href="{{ route('admin.vehicles.index',['status'=>'all']) }}" class="status-tab {{ request('status','all')=='all'?'status-tab--active':'status-tab--inactive' }}">All ({{ $allCount }})</a>
                <a href="{{ route('admin.vehicles.index',['status'=>'active']) }}" class="status-tab {{ request('status')=='active'?'status-tab--active':'status-tab--inactive' }}">Active ({{ $activeCount }})</a>
                <a href="{{ route('admin.vehicles.index',['status'=>'inactive']) }}" class="status-tab {{ request('status')=='inactive'?'status-tab--active':'status-tab--inactive' }}">Inactive ({{ $inactiveCount }})</a>
                <a href="{{ route('admin.vehicles.index',['status'=>'pending']) }}" class="status-tab {{ request('status')=='pending'?'status-tab--active':'status-tab--inactive' }}">Pending ({{ $pendingCount }})</a>
            </div>
        </div>
    </div>
    @if(session('success'))<div style="margin:16px;padding:12px;background:#c6f6d5;color:#22543d;border-radius:6px;">{{ session('success') }}</div>@endif
    @if(session('error'))<div style="margin:16px;padding:12px;background:#fed7d7;color:#742a2a;border-radius:6px;">{{ session('error') }}</div>@endif
    <div style="padding:16px;background:#f7fafc;border-bottom:1px solid #e2e8f0;">
        <form action="{{ route('admin.vehicles.index') }}" method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;">
            <div style="flex:1;min-width:200px;">
                <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, description" style="width:100%;padding:8px 12px;border:2px solid #e2e8f0;border-radius:6px;">
            </div>
            <input type="hidden" name="status" value="{{ request('status','all') }}">
            <button type="submit" style="padding:8px 16px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;">Filter</button>
        </form>
    </div>
    @if($vehicles->count()>0)
    <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($vehicles as $v)
        <tr>
            <td>{{ $v->id }}</td>
            <td><strong>{{ $v->name }}</strong></td>
            <td>{{ $v->vehicle_category ? \Illuminate\Support\Arr::get(\App\Models\Vehicle::CATEGORIES, $v->vehicle_category, $v->vehicle_category) : '—' }}</td>
            <td>{{ $v->capacity_seats ?? '—' }}</td>
            <td>@if($v->status==='active')<span class="badge badge-success">Active</span>@elseif($v->status==='inactive')<span class="badge badge-danger">Inactive</span>@else<span class="badge" style="background:#fbbf24;color:white;">Pending</span>@endif</td>
            <td>
                <a href="{{ route('admin.vehicles.show',$v->id) }}" style="padding:6px 12px;background:#48bb78;color:white;border-radius:6px;text-decoration:none;font-size:12px;">View</a>
                <a href="{{ route('admin.vehicles.edit',$v->id) }}" style="padding:6px 12px;background:#4299e1;color:white;border-radius:6px;text-decoration:none;font-size:12px;">Edit</a>
                <form action="{{ route('admin.vehicles.destroy',$v->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this vehicle?');">@csrf @method('DELETE')<button type="submit" style="padding:6px 12px;background:#e53e3e;color:white;border:none;border-radius:6px;font-size:12px;cursor:pointer;">Delete</button></form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div style="margin-top:20px;">{{ $vehicles->appends(request()->query())->links() }}</div>
    @else
    <div class="empty-state">
        <p>No vehicles found.</p>
        <a href="{{ route('admin.vehicles.create') }}" style="margin-top:16px;display:inline-block;background:#667eea;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;">Add First Vehicle</a>
    </div>
    @endif
</div>
@endsection
