@extends('admin.layouts.app')

@section('title', 'Sightseeing Bookings')
@section('page-title', 'Sightseeing Bookings')

@section('content')
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 class="card-title" style="margin-bottom: 4px;">Sightseeing Bookings</h2>
                <p style="color: #718096; font-size: 14px;">All sightseeing bookings created by users.</p>
            </div>
        </div>

        @if($bookings->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon">🏔️</div>
                <p>No sightseeing bookings found.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f7fafc; text-align: left;">
                            <th style="padding: 12px;">ID</th>
                            <th style="padding: 12px;">Sightseeing</th>
                            <th style="padding: 12px;">Option</th>
                            <th style="padding: 12px;">Booked By</th>
                            <th style="padding: 12px;">Date</th>
                            <th style="padding: 12px;">Pax</th>
                            <th style="padding: 12px;">Price</th>
                            <th style="padding: 12px;">Status</th>
                            <th style="padding: 12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px;">#{{ $booking->id }}</td>
                                <td style="padding: 12px;">
                                    @if($booking->sightseeing)
                                        <a href="{{ route('admin.sightseeings.show', $booking->sightseeing_id) }}" style="color: #2b6cb0; text-decoration: none; font-weight: 600;">
                                            {{ $booking->sightseeing->title }}
                                        </a><br>
                                    @else
                                        <strong>N/A</strong><br>
                                    @endif
                                    <small style="color: #718096;">ID: {{ $booking->sightseeing_id }}</small>
                                </td>
                                <td style="padding: 12px;">
                                    @if($booking->sightseeingOption)
                                        <span style="font-weight: 600;">{{ $booking->sightseeingOption->name }}</span>
                                    @else
                                        <span style="color: #718096;">—</span>
                                    @endif
                                </td>
                                <td style="padding: 12px;">
                                    @if($booking->user)
                                        <a href="{{ route('admin.users.edit', $booking->user_id) }}" style="color: #2b6cb0; text-decoration: none;">
                                            {{ $booking->user->name }}
                                        </a><br>
                                    @else
                                        N/A<br>
                                    @endif
                                    <small style="color: #718096;">ID: {{ $booking->user_id }}</small>
                                </td>
                                <td style="padding: 12px;">{{ $booking->booking_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                <td style="padding: 12px;">{{ $booking->pax_count }}</td>
                                <td style="padding: 12px;">
                                    @if($booking->price !== null)
                                        {{ $booking->currency ?? 'CHF' }} {{ number_format((float) $booking->price, 2) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td style="padding: 12px;">
                                    @php
                                        $badge = [
                                            'pending' => ['text' => 'Pending', 'class' => 'badge badge-warning'],
                                            'confirmed' => ['text' => 'Confirmed', 'class' => 'badge badge-success'],
                                            'cancelled' => ['text' => 'Cancelled', 'class' => 'badge badge-danger'],
                                        ][$booking->status] ?? ['text' => ucfirst($booking->status), 'class' => 'badge'];
                                    @endphp
                                    <span class="{{ $badge['class'] }}">{{ $badge['text'] }}</span>
                                </td>
                                <td style="padding: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="{{ route('admin.sightseeing-bookings.show', $booking->id) }}"
                                       style="padding: 6px 12px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                        View
                                    </a>
                                    @if($booking->status !== 'confirmed')
                                        <form action="{{ route('admin.sightseeing-bookings.status', $booking->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit"
                                                    style="padding: 6px 12px; background: #48bb78; color: white; border-radius: 6px; border: none; font-size: 12px; cursor: pointer;">
                                                Confirm
                                            </button>
                                        </form>
                                    @endif
                                    @if($booking->status !== 'cancelled')
                                        <form action="{{ route('admin.sightseeing-bookings.status', $booking->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit"
                                                    style="padding: 6px 12px; background: #e53e3e; color: white; border-radius: 6px; border: none; font-size: 12px; cursor: pointer;">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding: 16px;">
                {{ $bookings->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection
