@extends('admin.layouts.app')

@section('title', 'Add Private Venue')
@section('page-title', 'Add Private Venue')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Add event venue</h2>
            <a href="{{ route('admin.private-venues.index') }}" style="color:#667eea;text-decoration:none;font-size:14px;">← Back to list</a>
        </div>

        @if($errors->any())
            <div style="background:#f8d7da;color:#721c24;padding:12px;margin:20px;border-radius:6px;">
                <ul style="margin:8px 0 0 20px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('admin.private-venues.store') }}" method="POST" enctype="multipart/form-data" style="max-width:1200px;padding:20px;">
            @csrf
            @include('admin.private-venues._form', ['venue' => $venue, 'spaces' => $spaces])
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" style="background:#667eea;color:white;padding:12px 24px;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Create venue</button>
                <a href="{{ route('admin.private-venues.index') }}" style="padding:12px 24px;background:#e2e8f0;color:#2d3748;border-radius:8px;text-decoration:none;font-weight:600;">Cancel</a>
            </div>
        </form>
    </div>
@endsection
