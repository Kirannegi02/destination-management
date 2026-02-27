@extends('admin.layouts.app')

@section('title', 'Bulk Export Transports')
@section('page-title', 'Bulk Export Transports')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Export Transports</h2>
        </div>

        <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #f8fafc;">
                <h3 style="margin-bottom: 8px; color: #2d3748;">Step 1</h3>
                <p style="color: #4a5568; font-size: 14px; margin-bottom: 12px;">Select format and optional filters. Headers match the import template.</p>
                <form action="{{ route('admin.transports.export') }}" method="GET" style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="font-weight: 600; color: #2d3748;">File format</label>
                    <select name="format" style="padding: 10px 12px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px;">
                        <option value="xls">Excel (.xls)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                    <label style="font-weight: 600; color: #2d3748;">Status</label>
                    <select name="status" style="padding: 10px 12px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px;">
                        <option value="all">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                    @if($vehicles->isNotEmpty())
                        <label style="font-weight: 600; color: #2d3748;">Vehicle</label>
                        <select name="vehicle_id" style="padding: 10px 12px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px;">
                            <option value="">All Vehicles</option>
                            @foreach($vehicles as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button type="submit" style="padding: 10px 14px; background: #1e3a8a; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer;">Export Data</button>
                </form>
            </div>

            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: white;">
                <h3 style="margin-bottom: 8px; color: #2d3748;">Step 2</h3>
                <p style="color: #4a5568; font-size: 14px;">Edit the downloaded file if needed, then re-import via Bulk Import. Keep headers unchanged.</p>
            </div>
        </div>
    </div>
@endsection
