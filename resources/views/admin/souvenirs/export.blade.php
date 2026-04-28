@extends('admin.layouts.app')

@section('title', 'Bulk Export Souvenirs')
@section('page-title', 'Bulk Export Souvenirs')

@section('content')
    <div class="card">
        <div class="card-header" style="align-items: flex-start;">
            <div>
                <h2 class="card-title">Export Souvenirs</h2>
                <p style="color: #718096; font-size: 14px; margin-top: 4px;">
                    Download souvenir data as XLS or CSV. Column names match the import template. Image URLs are exported in the <strong>image_urls</strong> column (comma-separated) and can be re-imported.
                </p>
            </div>
        </div>

        <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #f8fafc;">
                <h3 style="margin-bottom: 8px; color: #2d3748;">Step 1</h3>
                <form action="{{ route('admin.souvenirs.export') }}" method="GET" style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="font-weight: 600; color: #2d3748;">File format</label>
                    <select name="format" style="padding: 10px 12px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px;">
                        <option value="xls">Excel (.xls)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                    <button type="submit" style="padding: 10px 14px; background: #1e3a8a; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer;">Export All Data</button>
                </form>
            </div>
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: white;">
                <h3 style="margin-bottom: 8px; color: #2d3748;">Step 2</h3>
                <p style="color: #4a5568; font-size: 14px;">
                    Use the downloaded file to edit and re-import via Bulk Import. Keep column names unchanged. Add or edit souvenirs in the admin to attach multiple images.
                </p>
            </div>
        </div>
    </div>
@endsection
