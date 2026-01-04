@extends('admin.layouts.app')

@section('title', 'Guide Details')
@section('page-title', 'Guide Details')

@section('content')
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h2 class="card-title">{{ $guide->title }}</h2>
            <a href="{{ route('admin.guides.index') }}" style="color:#667eea; text-decoration:none; font-size:14px;">← Back to Guides List</a>
        </div>

        <div style="padding:20px; display:grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap:20px;">
            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Location & Language</h4>
                <p style="color:#4a5568;">City: <strong>{{ $guide->city ?? 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Country: <strong>{{ $guide->country ?? 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Language: <strong>{{ $guide->language ?? 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Status: <strong>{{ ucfirst($guide->status) }}</strong></p>
            </div>

            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Service</h4>
                <p style="color:#4a5568;">Date: <strong>{{ $guide->service_date ? $guide->service_date->format('d M Y') : 'Flexible' }}</strong></p>
                <p style="color:#4a5568;">Start: <strong>{{ $guide->start_time ? $guide->start_time->format('H:i') : 'N/A' }}</strong></p>
                <p style="color:#4a5568;">End: <strong>{{ $guide->end_time ? $guide->end_time->format('H:i') : 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Duration: <strong>{{ $guide->duration_hours ? $guide->duration_hours . ' hrs' : 'N/A' }}</strong></p>
                <p style="color:#4a5568;">Price: <strong>{{ $guide->price ? '₹'.number_format($guide->price,2) : 'N/A' }}</strong></p>
            </div>

            <div style="background:#f7fafc; padding:16px; border-radius:10px; border:1px solid #e2e8f0;">
                <h4 style="margin-bottom:8px; color:#2d3748;">Meeting Points</h4>
                <p style="color:#4a5568;">Start (Meeting): <strong>{{ $guide->start_point ?? 'N/A' }}</strong></p>
                <p style="color:#4a5568;">End (Drop): <strong>{{ $guide->end_point ?? 'N/A' }}</strong></p>
            </div>
        </div>

        <div style="padding:20px;">
            <h4 style="color:#2d3748; margin-bottom:10px;">Description</h4>
            <p style="color:#4a5568; line-height:1.6;">{{ $guide->description ?: 'No description provided.' }}</p>

            @if($guide->notes)
                <h4 style="color:#2d3748; margin:16px 0 8px;">Notes</h4>
                <p style="color:#4a5568; line-height:1.6;">{{ $guide->notes }}</p>
            @endif
        </div>
    </div>
@endsection


