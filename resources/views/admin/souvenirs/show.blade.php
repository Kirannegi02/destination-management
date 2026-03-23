@extends('admin.layouts.app')

@section('title', 'Souvenir Details')
@section('page-title', 'Souvenir Details')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ $souvenir->name }}</h2>
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="{{ route('admin.souvenirs.edit', $souvenir->id) }}"
                   style="padding: 8px 16px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">✏️ Edit</a>
                <a href="{{ route('admin.souvenirs.index') }}" style="color: #667eea; text-decoration: none; font-size: 14px;">← Back to List</a>
            </div>
        </div>

        <div style="padding: 20px;">
            <div style="margin-bottom: 24px;">
                <span class="badge {{ $souvenir->status_badge_class }}" style="font-size: 14px; padding: 8px 16px;">{{ ucfirst($souvenir->status) }}</span>
            </div>

            @if($souvenir->images && count($souvenir->images) > 0)
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">Images</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">
                        @foreach($souvenir->images as $imagePath)
                            <img src="{{ \App\Services\ImageService::getUrl($imagePath) }}" alt="Souvenir"
                                 style="width: 100%; height: 180px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                        @endforeach
                    </div>
                </div>
            @endif

            <div style="display: grid; gap: 12px; max-width: 600px;">
                <div>
                    <strong style="color: #4a5568;">Name:</strong>
                    <span style="color: #2d3748;">{{ $souvenir->name }}</span>
                </div>
                @if($souvenir->description)
                    <div>
                        <strong style="color: #4a5568;">Description:</strong>
                        <p style="color: #2d3748; line-height: 1.6;">{{ $souvenir->description }}</p>
                    </div>
                @endif
                <div>
                    <strong style="color: #4a5568;">Price:</strong>
                    <span style="color: #2d3748;">{{ $souvenir->currency }} {{ number_format((float)$souvenir->price, 2) }}</span>
                </div>
                <div>
                    <strong style="color: #4a5568;">Min order quantity:</strong>
                    <span style="color: #2d3748;">{{ $souvenir->min_order_quantity }}</span>
                </div>
                <div>
                    <strong style="color: #4a5568;">Stock:</strong>
                    @if($souvenir->stock !== null && $souvenir->stock <= 0)
                        <span style="color: #e53e3e; font-weight: 600;">Out of stock</span>
                    @else
                        <span style="color: #2d3748;">{{ $souvenir->stock ?? 0 }}</span>
                    @endif
                </div>
                <div>
                    <strong style="color: #4a5568;">City:</strong>
                    <span style="color: #2d3748;">{{ $souvenir->city ?? '—' }}</span>
                </div>
                <div>
                    <strong style="color: #4a5568;">Country:</strong>
                    <span style="color: #2d3748;">{{ $souvenir->country ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
