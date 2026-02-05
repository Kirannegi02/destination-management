@extends('admin.layouts.app')

@section('title', 'Bulk Import Transports')
@section('page-title', 'Bulk Import Transports')

@section('content')
    <div class="card">
        <div class="card-header" style="align-items: flex-start;">
            <div>
                <h2 class="card-title">Upload File</h2>
                <p style="color: #718096; font-size: 14px; margin-top: 4px;">Supported: CSV, XLS, XLSX. Use vehicle_id from Vehicles list.</p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="{{ route('admin.transports.import.sample', ['format' => 'csv']) }}" style="padding: 8px 12px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">Download CSV Sample</a>
                <a href="{{ route('admin.transports.import.sample', ['format' => 'xls']) }}" style="padding: 8px 12px; background: #667eea; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">Download Excel Sample</a>
                <a href="{{ route('admin.transports.export.page') }}" style="padding: 8px 12px; background: #48bb78; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">Export Page</a>
            </div>
        </div>

        <div style="padding: 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">
            <form action="{{ route('admin.transports.import') }}" method="POST" enctype="multipart/form-data" style="background: #f7fafc; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="font-weight: 600; color: #2d3748;">Choose file</label>
                    <input type="file" name="file" accept=".csv,.xls,.xlsx" style="padding: 10px; border: 2px dashed #cbd5e0; border-radius: 8px; background: white;">
                    @error('file')<div style="color: #e53e3e; font-size: 14px;">{{ $message }}</div>@enderror
                    <button type="submit" style="padding: 10px 14px; background: #1e3a8a; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer;">Import Transports</button>
                </div>
                @if(session('import_errors') && count(session('import_errors')))
                    <div style="margin-top: 16px; background: #fffaf0; padding: 12px; border-radius: 8px; border: 1px solid #fbd38d;">
                        <strong style="color: #c05621;">Some rows were skipped:</strong>
                        <ul style="margin-top: 8px; color: #744210; padding-left: 20px;">
                            @foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
            </form>

            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                <h3 style="margin-bottom: 10px; color: #2d3748;">Columns</h3>
                <p style="color: #4a5568; font-size: 14px; margin-bottom: 10px;">Required: vehicle_id, price_per_km, status.</p>
                @foreach($columns as $col)<span style="display:block;">{{ $col }}</span>@endforeach
                <p style="margin-top:12px; font-size:13px; color:#4a5568;">Vehicle IDs: @foreach($vehicles as $v) {{ $v->id }}=>{{ $v->name }} @endforeach</p>
            </div>
        </div>
    </div>
@endsection
