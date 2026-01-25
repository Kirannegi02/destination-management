@extends('admin.layouts.app')

@section('title', 'Import Sightseeings')
@section('page-title', 'Import Sightseeings')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Bulk Import Sightseeings</h2>
            <a href="{{ route('admin.sightseeings.index') }}" style="color:#667eea; text-decoration:none; font-size:14px;">← Back to list</a>
        </div>

        @if(session('error'))
            <div style="background:#fed7d7; color:#c53030; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
                {{ session('error') }}
            </div>
        @endif
        @if(session('import_errors'))
            <div style="background:#fffaf0; color:#975a16; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
                <strong>Row errors:</strong>
                <ul style="margin:8px 0 0 20px;">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="margin-bottom:16px;">
            <p style="color:#4a5568;">Download sample and keep columns exactly as shown.</p>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="{{ route('admin.sightseeings.import.sample', ['format' => 'xls']) }}" style="padding:8px 14px; background:#1e3a8a; color:white; border-radius:6px; text-decoration:none;">Download Sample (Excel)</a>
                <a href="{{ route('admin.sightseeings.import.sample', ['format' => 'csv']) }}" style="padding:8px 14px; background:#2b6cb0; color:white; border-radius:6px; text-decoration:none;">Download Sample (CSV)</a>
            </div>
        </div>

        <form action="{{ route('admin.sightseeings.import') }}" method="POST" enctype="multipart/form-data" style="max-width:700px;">
            @csrf
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:6px; font-weight:600; color:#2d3748;">Upload File (CSV/XLS/XLSX)</label>
                <input type="file" name="file" required
                       style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                @error('file')
                    <div style="color:#e53e3e; font-size:12px; margin-top:4px;">{{ $message }}</div>
                @enderror
            </div>
            <p style="color:#718096; font-size:13px; margin-bottom:16px;">
                Supported: CSV, XLS, XLSX. Max ~5MB. Required columns: title, status.
            </p>
            <button type="submit" style="padding:10px 18px; background:#667eea; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
                Import
            </button>
        </form>

        <div style="margin-top:24px;">
            <h4 style="margin-bottom:8px;">Column reference</h4>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:10px;">
                @foreach($columns as $col)
                    <div style="padding:8px 10px; background:#f7fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; color:#2d3748;">
                        {{ $col }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

