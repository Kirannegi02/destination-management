@extends('admin.layouts.app')

@section('title', 'Bulk Export Guides')
@section('page-title', 'Bulk Export Guides')

@section('content')
    <div class="card">
        <div class="card-header" style="align-items: flex-start;">
            <div>
                <h2 class="card-title">Export Guides</h2>
            </div>
        </div>

        <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #f8fafc;">
                <h3 style="margin-bottom: 8px; color: #2d3748;">Step 1</h3>
                <p style="color: #4a5568; font-size: 14px; margin-bottom: 12px;">
                    Select format. Headers match the import template (title, description, country, city, language, service_date, start_point, end_point, start_time, end_time, duration_hours, half_day_price, full_day_price, extra_hour_price, status, notes).
                </p>
                <form action="{{ route('admin.guides.export') }}" method="GET" style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="font-weight: 600; color: #2d3748;">File format</label>
                    <select name="format" style="padding: 10px 12px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px;">
                        <option value="xls">Excel (.xls)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                    <button type="submit"
                            style="padding: 10px 14px; background: #1e3a8a; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer;">
                        Export All Data
                    </button>
                </form>
            </div>

            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: white;">
                <h3 style="margin-bottom: 8px; color: #2d3748;">Step 2</h3>
                <p style="color: #4a5568; font-size: 14px; margin-bottom: 12px;">
                    Edit the downloaded file if needed, then re-import via Bulk Import. Keep headers unchanged for smooth import.
                </p>
                <ul style="color: #4a5568; font-size: 14px; margin-left: 18px; list-style: disc;">
                    <li>Use .xls for Excel; .csv for lightweight edits.</li>
                    <li>Headers: title, description, country, city, language, service_date, start_point, end_point, start_time, end_time, duration_hours, half_day_price, full_day_price, extra_hour_price, status, notes.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection


