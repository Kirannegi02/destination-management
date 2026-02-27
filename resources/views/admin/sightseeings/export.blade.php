@extends('admin.layouts.app')

@section('title', 'Export Sightseeings')
@section('page-title', 'Export Sightseeings')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Export Sightseeings</h2>
            <a href="{{ route('admin.sightseeings.index') }}" style="color:#667eea; text-decoration:none; font-size:14px;">← Back to list</a>
        </div>

        <p style="color:#4a5568; margin-bottom:12px;">Choose format to download current sightseeing data.</p>

        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
            <a href="{{ route('admin.sightseeings.export', ['format' => 'xls']) }}"
               style="padding:10px 16px; background:#1e3a8a; color:white; border-radius:8px; text-decoration:none;">
                Export Excel
            </a>
            <a href="{{ route('admin.sightseeings.export', ['format' => 'csv']) }}"
               style="padding:10px 16px; background:#2b6cb0; color:white; border-radius:8px; text-decoration:none;">
                Export CSV
            </a>
        </div>
    </div>
@endsection

<!-- in below api
{{local_url}}/api/restaurants?search=Italian&status=ac

add number of rooms, locatrion in search api  -->