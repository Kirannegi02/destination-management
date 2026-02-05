@extends('admin.layouts.app')
@section('title', 'Add Vehicle')
@section('page-title', 'Add Vehicle')
@section('content')
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h2 class="card-title">Add Vehicle</h2>
        <a href="{{ route('admin.vehicles.index') }}" style="color:#667eea;text-decoration:none;">Back to Vehicles</a>
    </div>
    @if($errors->any())
    <div style="background:#f8d7da;padding:12px;margin:20px;border-radius:6px;">
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif
    <form action="{{ route('admin.vehicles.store') }}" method="POST" style="padding:20px;">
        @csrf
        <div style="margin-bottom:20px;"><label>Name (required)</label><input type="text" name="name" value="{{ old('name') }}" required style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div style="margin-bottom:20px;"><label>Capacity (seats)</label><input type="number" name="capacity_seats" value="{{ old('capacity_seats') }}" min="1" max="100" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"></div>
        <div style="margin-bottom:20px;"><label>Description</label><textarea name="description" rows="3" style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;">{{ old('description') }}</textarea></div>
        <div style="margin-bottom:20px;"><label>Status (required)</label><select name="status" required style="width:100%;max-width:200px;padding:10px;border:2px solid #e2e8f0;border-radius:8px;"><option value="active" {{ old('status','active')=='active'?'selected':'' }}>Active</option><option value="inactive" {{ old('status')=='inactive'?'selected':'' }}>Inactive</option><option value="pending" {{ old('status')=='pending'?'selected':'' }}>Pending</option></select></div>
        <button type="submit" style="padding:10px 20px;background:#48bb78;color:white;border:none;border-radius:8px;cursor:pointer;">Create Vehicle</button>
    </form>
</div>
@endsection
