@extends('admin.layouts.app')

@section('title', $typeLabel . ' Management')
@section('page-title', $typeLabel . ' Management')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ $typeLabel }}</h2>
            <a href="{{ route('admin.services.create', ['type' => $type]) }}" 
               style="background: #667eea; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-block;">
                + Add New {{ $typeLabel }}
            </a>
        </div>

        @if($services->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>City</th>
                        <th>Price</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($services as $service)
                        <tr>
                            <td>{{ $service->id }}</td>
                            <td>
                                <strong>{{ $service->name }}</strong>
                                @if($service->description)
                                    <br><small style="color: #718096;">{{ Str::limit($service->description, 50) }}</small>
                                @endif
                            </td>
                            <td>{{ $service->location ?? 'N/A' }}</td>
                            <td>{{ $service->city ?? 'N/A' }}</td>
                            <td>
                                @if($service->price)
                                    {{ $service->currency }} {{ number_format($service->price, 2) }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $service->capacity ?? 'N/A' }}</td>
                            <td>
                                @if($service->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="{{ route('admin.services.edit', $service->id) }}" 
                                       style="padding: 6px 12px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.services.destroy', $service->id) }}" 
                                          method="POST" 
                                          style="display: inline;"
                                          onsubmit="return confirm('Are you sure you want to delete this service?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                style="padding: 6px 12px; background: #e53e3e; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top: 20px; display: flex; justify-content: center;">
                {{ $services->appends(['type' => $type])->links() }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <p>No {{ strtolower($typeLabel) }} found.</p>
                <a href="{{ route('admin.services.create', ['type' => $type]) }}" 
                   style="margin-top: 16px; display: inline-block; background: #667eea; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                    Create First {{ $typeLabel }}
                </a>
            </div>
        @endif
    </div>
@endsection



