@extends('admin.layouts.app')
@section('title', 'Bulk Import Vehicles')
@section('page-title', 'Bulk Import Vehicles')
@section('content')
<div class="card">
    <div class="card-header" style="align-items:flex-start;">
        <div><h2 class="card-title">Upload File</h2><p style="color:#718096;font-size:14px;">Supported: CSV, XLS, XLSX. Required: name, status.</p></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('admin.vehicles.import.sample',['format'=>'csv']) }}" style="padding:8px 12px;background:#4299e1;color:white;border-radius:6px;text-decoration:none;">Download CSV Sample</a>
            <a href="{{ route('admin.vehicles.import.sample',['format'=>'xls']) }}" style="padding:8px 12px;background:#667eea;color:white;border-radius:6px;text-decoration:none;">Download Excel Sample</a>
            <a href="{{ route('admin.vehicles.export.page') }}" style="padding:8px 12px;background:#48bb78;color:white;border-radius:6px;text-decoration:none;">Export Page</a>
        </div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:2fr 1fr;gap:20px;">
        <form action="{{ route('admin.vehicles.import') }}" method="POST" enctype="multipart/form-data" style="background:#f7fafc;padding:16px;border-radius:10px;border:1px solid #e2e8f0;">
            @csrf
            <label style="font-weight:600;">Choose file</label>
            <input type="file" name="file" accept=".csv,.xls,.xlsx" style="padding:10px;margin:8px 0;display:block;border:2px dashed #cbd5e0;border-radius:8px;">
            @error('file')<div style="color:#e53e3e;">{{ $message }}</div>@enderror
            <button type="submit" style="padding:10px 14px;background:#1e3a8a;color:white;border:none;border-radius:8px;cursor:pointer;">Import Vehicles</button>
            @if(session('import_errors') && count(session('import_errors')))
            <div style="margin-top:16px;background:#fffaf0;padding:12px;border-radius:8px;"><strong>Some rows skipped:</strong><ul>@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
        </form>
        <div style="background:white;border:1px solid #e2e8f0;border-radius:10px;padding:16px;">
            <h3 style="margin-bottom:10px;">Columns</h3>
            <p>Required: name, status.</p>
            @foreach($columns as $col)<span style="display:block;">{{ $col }}</span>@endforeach
        </div>
    </div>
</div>
@endsection
