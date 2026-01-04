@extends('admin.layouts.app')

@section('title', 'Edit Guide')
@section('page-title', 'Edit Guide')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Edit Guide</h2>
            <a href="{{ route('admin.guides.index') }}" style="color:#667eea; text-decoration:none; font-size:14px;">← Back to Guides List</a>
        </div>

        @if($errors->any())
            <div style="background:#f8d7da; color:#721c24; padding:12px; border-radius:6px; margin:20px; border:1px solid #f5c6cb;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin:8px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.guides.update', $guide->id) }}" method="POST" style="max-width:1200px; padding:20px;">
            @csrf
            @method('PUT')

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:20px;">Basic Information</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Guide Title <span style="color:#e53e3e;">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $guide->title) }}" required
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Language</label>
                        <input type="text" name="language" value="{{ old('language', $guide->language) }}" placeholder="e.g., English, Hindi"
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Description</label>
                    <textarea name="description" rows="4" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px; resize:vertical;">{{ old('description', $guide->description) }}</textarea>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Country</label>
                        <input type="text" name="country" value="{{ old('country', $guide->country) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">City</label>
                        <input type="text" name="city" value="{{ old('city', $guide->city) }}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Status <span style="color:#e53e3e;">*</span></label>
                        <select name="status" required style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                            <option value="active" {{ old('status', $guide->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $guide->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="pending" {{ old('status', $guide->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:30px;">
                <h3 style="color:#2d3748; border-bottom:2px solid #e2e8f0; padding-bottom:8px; margin-bottom:20px;">Service Details</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Service Date</label>
                        <input type="date" name="service_date" value="{{ old('service_date', optional($guide->service_date)->format('Y-m-d')) }}"
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Duration (Hours)</label>
                        <input type="number" name="duration_hours" value="{{ old('duration_hours', $guide->duration_hours) }}" min="1" max="72"
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Start Point (Meeting Point)</label>
                        <input type="text" name="start_point" value="{{ old('start_point', $guide->start_point) }}"
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">End Point (Drop off)</label>
                        <input type="text" name="end_point" value="{{ old('end_point', $guide->end_point) }}"
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Start Time</label>
                        <input type="time" name="start_time" value="{{ old('start_time', optional($guide->start_time)->format('H:i')) }}"
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">End Time</label>
                        <input type="time" name="end_time" value="{{ old('end_time', optional($guide->end_time)->format('H:i')) }}"
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Price</label>
                        <input type="number" name="price" value="{{ old('price', $guide->price) }}" step="0.01" min="0"
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#2d3748;">Notes</label>
                        <input type="text" name="notes" value="{{ old('notes', $guide->notes) }}"
                               style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:12px; margin-top:30px;">
                <button type="submit"
                        style="background:#667eea; color:white; padding:12px 24px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;">
                    Update Guide
                </button>
                <a href="{{ route('admin.guides.index') }}"
                   style="background:#e2e8f0; color:#2d3748; padding:12px 24px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:600; display:inline-block;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection


