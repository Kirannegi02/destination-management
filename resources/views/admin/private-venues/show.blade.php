@extends('admin.layouts.app')

@section('title', $venue->name)
@section('page-title', 'Private Venue Details')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ $venue->name }}</h2>
            <div style="display:flex;gap:10px;">
                <a href="{{ route('admin.private-venues.edit', $venue) }}" style="padding:8px 16px;background:#4299e1;color:white;border-radius:6px;text-decoration:none;">Edit</a>
                <a href="{{ route('admin.private-venues.index') }}" style="padding:8px 16px;background:#e2e8f0;color:#2d3748;border-radius:6px;text-decoration:none;">← List</a>
            </div>
        </div>

        <div style="padding:24px;max-width:1100px;">
            @if(is_array($venue->images) && count($venue->images))
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
                    @foreach($venue->images as $img)
                        <img src="{{ \App\Services\ImageService::getUrl($img) }}" alt="" style="height:160px;border-radius:8px;object-fit:cover;">
                    @endforeach
                </div>
            @endif

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px;">
                <div>
                    <p style="color:#718096;margin-bottom:8px;">
                        <strong>{{ $venue->venue_type_label }}</strong>
                        @if($venue->brand_chain) · {{ $venue->brand_chain }} @endif
                        @if($venue->star_rating) · {{ $venue->star_rating }}★ @endif
                    </p>
                    @if($venue->highlights)<p style="font-style:italic;color:#4a5568;">{{ $venue->highlights }}</p>@endif
                    @if($venue->description)<p style="margin-top:12px;line-height:1.6;">{{ $venue->description }}</p>@endif
                </div>
                <div style="background:#f7fafc;padding:16px;border-radius:8px;">
                    <p><strong>Status:</strong> {{ ucfirst($venue->status) }}</p>
                    <p><strong>Location:</strong> {{ $venue->address }}</p>
                    <p>{{ $venue->location_label }}</p>
                    @if($venue->phone)<p><strong>Phone:</strong> {{ $venue->phone }}</p>@endif
                    @if($venue->email)<p><strong>Email:</strong> {{ $venue->email }}</p>@endif
                    @if($venue->website)<p><a href="{{ $venue->website }}" target="_blank">Website</a></p>@endif
                </div>
            </div>

            <h3 style="border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:16px;">Capacity overview</h3>
            <table class="table" style="margin-bottom:24px;">
                <tbody>
                    <tr><th style="width:220px;">Event size range</th><td>{{ $venue->min_event_size ?? '—' }} – {{ $venue->max_event_size ?? '—' }} guests</td></tr>
                    <tr><th>Total meeting space</th><td>{{ $venue->total_meeting_space_sqm ? $venue->total_meeting_space_sqm.' m²' : '—' }}</td></tr>
                    <tr><th>Largest room capacity</th><td>{{ $venue->largest_room_capacity ?? '—' }}</td></tr>
                    <tr><th>Meeting rooms</th><td>{{ $venue->number_of_meeting_rooms ?? $venue->spaces->count() }}</td></tr>
                    <tr><th>Sleeping rooms</th><td>{{ $venue->sleeping_rooms ?? '—' }}</td></tr>
                    <tr><th>Starting rate</th><td>{{ $venue->starting_rate_formatted ?? '—' }}</td></tr>
                </tbody>
            </table>

            @if(is_array($venue->event_types) && count($venue->event_types))
                <h3 style="margin-bottom:12px;">Event types</h3>
                <p style="margin-bottom:20px;">
                    @foreach($venue->event_types as $et)
                        <span class="badge badge-success" style="margin-right:6px;">{{ $eventTypesList[$et] ?? $et }}</span>
                    @endforeach
                </p>
            @endif

            @if(is_array($venue->amenities) && count($venue->amenities))
                <h3 style="margin-bottom:12px;">Venue amenities</h3>
                <p style="margin-bottom:24px;">
                    @foreach($venue->amenities as $am)
                        <span class="badge" style="background:#edf2f7;color:#2d3748;margin:4px 6px 4px 0;">{{ $venueAmenitiesList[$am] ?? $am }}</span>
                    @endforeach
                </p>
            @endif

            <h3 style="border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-bottom:16px;">Meeting space ({{ $venue->spaces->count() }} rooms)</h3>
            @if($venue->spaces->count())
                @foreach($venue->spaces as $room)
                    <div style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:16px;">
                        <h4 style="margin:0 0 8px;">{{ $room->name }}
                            @if($room->status === 'inactive')<span class="badge badge-danger">Inactive</span>@endif
                        </h4>
                        @if($room->description)<p style="color:#4a5568;font-size:14px;">{{ $room->description }}</p>@endif
                        @if($room->dimensions_label)<p><strong>Dimensions:</strong> {{ $room->dimensions_label }}</p>@endif
                        <p style="font-size:13px;margin:8px 0;">
                            @if($room->is_outdoor)<span class="badge">Outdoor</span>@endif
                            @if($room->is_private)<span class="badge">Private</span>@endif
                            @if($room->is_semi_private)<span class="badge">Semi-private</span>@endif
                            @if($room->wheelchair_accessible)<span class="badge badge-success">Accessible</span>@endif
                        </p>
                        @if(is_array($room->setup_capacities) && count($room->setup_capacities))
                            <table class="table" style="font-size:13px;margin-top:12px;">
                                <thead><tr><th>Setup</th><th>Max capacity</th></tr></thead>
                                <tbody>
                                    @foreach($room->setup_capacities as $setup => $cap)
                                        <tr>
                                            <td>{{ $setupTypes[$setup] ?? $setup }}</td>
                                            <td>{{ $cap }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endforeach
            @else
                <p style="color:#718096;">No meeting rooms defined yet.</p>
            @endif

            @if($venue->internal_notes)
                <h3 style="margin-top:24px;">Internal notes</h3>
                <p style="background:#fffaf0;padding:12px;border-radius:6px;">{{ $venue->internal_notes }}</p>
            @endif
        </div>
    </div>
@endsection
