@extends('admin.layouts.app')

@section('title', 'Sightseeing Details')
@section('page-title', 'Sightseeing Details')

@section('content')
    <div class="card">
        <div class="card-header" style="align-items:flex-start;">
            <div>
                <h2 class="card-title">{{ $sightseeing->title }}</h2>
                <p style="color:#718096; margin-top:4px;">{{ $sightseeing->city ?? 'N/A' }} {{ $sightseeing->country ? ', '.$sightseeing->country : '' }}</p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="{{ route('admin.sightseeings.edit', $sightseeing->id) }}" 
                   style="padding: 8px 14px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Edit
                </a>
                <a href="{{ route('admin.sightseeings.index') }}" 
                   style="padding: 8px 14px; background: #e2e8f0; color: #2d3748; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Back
                </a>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom:20px;">
            <div>
                <p><strong>Status:</strong> {{ ucfirst($sightseeing->status) }}</p>
                <p><strong>Featured:</strong> {{ $sightseeing->is_featured ? 'Yes' : 'No' }}</p>
                @if($sightseeing->start_location || $sightseeing->end_location)
                    <p><strong>Start / End:</strong> {{ $sightseeing->start_location ?? '—' }} → {{ $sightseeing->end_location ?? '—' }}</p>
                @endif
            </div>
            <div>
                @if($sightseeing->image)
                    <img src="{{ \App\Services\ImageService::getUrl($sightseeing->image) }}" alt="{{ $sightseeing->title }}" style="width:100%; max-width:360px; border-radius:10px; border:1px solid #e2e8f0;">
                @endif
            </div>
        </div>

        @if($sightseeing->description)
            <div style="margin-bottom: 16px;">
                <h3 style="margin-bottom: 8px;">Description</h3>
                <p style="color:#4a5568; line-height:1.6;">{{ $sightseeing->description }}</p>
            </div>
        @endif

        <div style="margin-top: 20px;">
            <h3 style="margin-bottom: 12px;">Options / Packages</h3>
            @if($sightseeing->options->count())
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Includes</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sightseeing->options as $option)
                            <tr>
                                <td>
                                    <strong>{{ $option->name }}</strong>
                                    @if($option->description)
                                        <br><small style="color:#4a5568;">{{ $option->description }}</small>
                                    @endif
                                    @if($option->availability_note)
                                        <br><small style="color:#718096;">{{ $option->availability_note }}</small>
                                    @endif
                                </td>
                                <td>{{ $option->duration_minutes ? $option->duration_minutes . ' mins' : '—' }}</td>
                                <td>{{ $option->base_price !== null ? (($option->currency ?? $sightseeing->currency ?? 'CHF') . ' ' . number_format($option->base_price,2)) : '—' }}</td>
                                <td>
                                    @php $includes = []; @endphp
                                    @if($option->includes_lunch) @php $includes[] = 'Lunch'; @endphp @endif
                                    @if($option->includes_transport) @php $includes[] = 'Transport'; @endphp @endif
                                    {{ count($includes) ? implode(', ', $includes) : '—' }}
                                </td>
                                <td>
                                    @if($option->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color:#718096;">No options configured yet.</p>
            @endif
        </div>
    </div>
@endsection

