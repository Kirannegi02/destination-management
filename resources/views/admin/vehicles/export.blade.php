@extends('admin.layouts.app')
@section('title', 'Bulk Export Vehicles')
@section('page-title', 'Bulk Export Vehicles')
@section('content')
<div class="card">
    <div class="card-header"><h2 class="card-title">Export Vehicles</h2></div>
    <div style="padding:20px;">
        <div style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;background:#f8fafc;max-width:400px;">
            <h3 style="margin-bottom:8px;">Step 1</h3>
            <p style="color:#4a5568;font-size:14px;margin-bottom:12px;">Select format. Headers match the import template.</p>
            <form action="{{ route('admin.vehicles.export') }}" method="GET" style="display:flex;flex-direction:column;gap:12px;">
                <label style="font-weight:600;">File format</label>
                <select name="format" style="padding:10px 12px;border:1px solid #cbd5e0;border-radius:8px;"><option value="xls">Excel (.xls)</option><option value="csv">CSV (.csv)</option></select>
                <label style="font-weight:600;">Status</label>
                <select name="status" style="padding:10px 12px;border:1px solid #cbd5e0;border-radius:8px;"><option value="all">All</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="pending">Pending</option></select>
                <button type="submit" style="padding:10px 14px;background:#1e3a8a;color:white;border:none;border-radius:8px;cursor:pointer;">Export Data</button>
            </form>
        </div>
    </div>
</div>
@endsection
