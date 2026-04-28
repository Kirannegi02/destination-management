@extends('admin.layouts.app')

@section('title', 'Bulk Export Meals')
@section('page-title', 'Bulk Export Meals')

@section('content')
    <div class="card">
        <div class="card-header" style="align-items: flex-start;">
            <div>
                <h2 class="card-title">Export meals</h2>
                <p style="color: #718096; font-size: 14px; margin-top: 4px;">
                    Download rows with restaurant name, phone, and meal fields. Re-import after edits; global meal rows sync descriptions from <strong>Global menu</strong>.
                </p>
            </div>
        </div>

        <div style="padding: 20px;">
            <form action="{{ route('admin.meals.export') }}" method="GET" style="display: flex; flex-direction: column; gap: 12px; max-width: 400px;">
                <label style="font-weight: 600;">Format</label>
                <select name="format" style="padding: 10px; border-radius: 8px; border: 1px solid #cbd5e0;">
                    <option value="xls">Excel (.xls)</option>
                    <option value="csv">CSV (.csv)</option>
                </select>
                <label style="font-weight: 600;">Restaurant (optional)</label>
                <select name="restaurant_id" style="padding: 10px; border-radius: 8px; border: 1px solid #cbd5e0;">
                    <option value="">All</option>
                    @foreach($restaurants as $r)
                        <option value="{{ $r->id }}">{{ $r->restaurant_name }}</option>
                    @endforeach
                </select>
                <label style="font-weight: 600;">Status</label>
                <select name="status" style="padding: 10px; border-radius: 8px; border: 1px solid #cbd5e0;">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button type="submit" style="padding: 10px 14px; background: #1e3a8a; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Download
                </button>
            </form>
        </div>
    </div>
@endsection
