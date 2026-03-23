@extends('admin.layouts.app')

@section('title', 'Restaurants Management')
@section('page-title', 'Restaurants Management')

@section('content')
    <style>
        .status-tab {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
        }
        .status-tab--active {
            background: #667eea;
            color: white;
        }
        .status-tab--inactive {
            color: #4a5568;
        }
    </style>
    @php
        $exportFilters = array_filter([
            'status' => $status !== 'all' ? $status : null,
            'city' => request('city'),
            'cuisine_type' => request('cuisine_type'),
        ]);
    @endphp
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                Restaurants
                <span style="font-size: 14px; font-weight: normal; color: #718096;">
                    ({{ $allCount }} total)
                </span>
            </h2>
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('admin.restaurants.create') }}" 
                   style="padding: 8px 16px; background: #48bb78; color: white; border: none; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
                    <span>+</span> Add Restaurant
                </a>
                <div style="display: flex; gap: 6px;">
                    <a href="{{ route('admin.restaurants.export', array_merge($exportFilters, ['format' => 'xls'])) }}"
                       style="padding: 8px 14px; background: #1e3a8a; color: white; border-radius: 6px; text-decoration: none; font-size: 13px;">
                        Export Excel
                    </a>
                    <a href="{{ route('admin.restaurants.export', array_merge($exportFilters, ['format' => 'csv'])) }}"
                       style="padding: 8px 14px; background: #2b6cb0; color: white; border-radius: 6px; text-decoration: none; font-size: 13px;">
                        Export CSV
                    </a>
                </div>
                
                <!-- Status Tabs -->
                <div style="display: flex; gap: 4px; background: #f7fafc; padding: 4px; border-radius: 6px;">
                    <a href="{{ route('admin.restaurants.index', ['status' => 'all']) }}" 
                       class="status-tab {{ $status == 'all' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        All ({{ $allCount }})
                    </a>
                    <a href="{{ route('admin.restaurants.index', ['status' => 'active']) }}" 
                       class="status-tab {{ $status == 'active' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        Active ({{ $activeCount }})
                    </a>
                    <a href="{{ route('admin.restaurants.index', ['status' => 'inactive']) }}" 
                       class="status-tab {{ $status == 'inactive' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        Inactive ({{ $inactiveCount }})
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="padding: 16px; background: #f7fafc; border-bottom: 1px solid #e2e8f0;">
            <form action="{{ route('admin.restaurants.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
                <input type="hidden" name="status" value="{{ $status }}">
                
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Search</label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search restaurants..." 
                           style="width: 100%; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                </div>
                
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">City</label>
                    <select name="city" 
                            style="width: 100%; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Cities</option>
                        @foreach($cities as $city)
                            <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>
                                {{ $city }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Cuisine Type</label>
                    <select name="cuisine_type" 
                            style="width: 100%; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Cuisines</option>
                        @foreach($cuisineTypes as $cuisineType)
                            <option value="{{ $cuisineType }}" {{ request('cuisine_type') == $cuisineType ? 'selected' : '' }}>
                                {{ $cuisineType }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Price</label>
                    <input type="number" 
                           name="price" 
                           value="{{ request('price') }}" 
                           step="0.01"
                           min="0"
                           placeholder="Filter by price"
                           style="width: 100%; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                </div>
                
                <div style="display: flex; gap: 8px;">
                    <button type="submit" 
                            style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        Filter
                    </button>
                    @if(request('search') || request('city') || request('cuisine_type') || request('price'))
                        <a href="{{ route('admin.restaurants.index', ['status' => $status]) }}" 
                           style="padding: 8px 16px; background: #e2e8f0; color: #2d3748; border-radius: 6px; text-decoration: none; font-size: 14px; display: inline-block;">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @if($restaurants->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Restaurant Name</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($restaurants as $restaurant)
                        <tr>
                            <td>{{ $restaurant->id }}</td>
                            <td>
                                @if($restaurant->images && count($restaurant->images) > 0)
                                    <img src="{{ \App\Services\ImageService::getUrl($restaurant->images[0]) }}" 
                                         alt="{{ $restaurant->restaurant_name }}" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                @else
                                    <div style="width: 60px; height: 60px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #718096; font-size: 24px;">
                                        🍽️
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $restaurant->restaurant_name }}</strong>
                                @if($restaurant->cuisine_type)
                                    <br><small style="color: #718096;">🍴 {{ $restaurant->cuisine_type }}</small>
                                @endif
                                @if($restaurant->price)
                                    <br><small style="color: #667eea;">{{ $restaurant->price_formatted }}</small>
                                @endif
                                @if($restaurant->seating_capacity)
                                    <br><small style="color: #4a5568;">👥 Capacity: {{ $restaurant->seating_capacity }} seats</small>
                                @endif
                            </td>
                            <td>
                                @if($restaurant->city)
                                    {{ $restaurant->city }}
                                    @if($restaurant->state)
                                        , {{ $restaurant->state }}
                                    @endif
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 12px;">
                                    <div>📞 {{ $restaurant->phone }}</div>
                                    @if($restaurant->email)
                                        <div style="color: #718096;">✉️ {{ $restaurant->email }}</div>
                                    @endif
                                    @if($restaurant->alternate_phone)
                                        <div style="color: #718096;">📱 Alt: {{ $restaurant->alternate_phone }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($restaurant->star_rating)
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <span style="color: #fbbf24;">⭐</span>
                                        <span>{{ $restaurant->star_rating }}/5</span>
                                    </div>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                @if($restaurant->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="{{ route('admin.restaurants.show', $restaurant->id) }}" 
                                       style="padding: 6px 12px; background: #48bb78; color: white; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                        View Details
                                    </a>
                                    <a href="{{ route('admin.restaurants.edit', $restaurant->id) }}" 
                                       style="padding: 6px 12px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.restaurants.destroy', $restaurant->id) }}" 
                                          method="POST" 
                                          style="display: inline;"
                                          onsubmit="return confirm('Are you sure you want to delete this restaurant? This action cannot be undone.');">
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
                {{ $restaurants->appends(request()->query())->links() }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">🍽️</div>
                <p>No restaurants found.</p>
                <a href="{{ route('admin.restaurants.create') }}" 
                   style="margin-top: 16px; display: inline-block; background: #667eea; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                    Create First Restaurant
                </a>
            </div>
        @endif
    </div>
@endsection

