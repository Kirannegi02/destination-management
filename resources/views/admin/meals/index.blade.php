@extends('admin.layouts.app')

@section('title', 'Meals Management')
@section('page-title', 'Meals Management')

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
        /* Bootstrap-4 pagination (no Tailwind in admin layout — default tailwind.blade.php SVGs render huge) */
        .pagination {
            display: flex;
            gap: 6px;
            list-style: none;
            padding-left: 0;
            margin: 0;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }
        .pagination .page-item .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #fff;
            color: #2d3748;
            text-decoration: none;
            font-size: 14px;
        }
        .pagination .page-item.active .page-link {
            background: #1e3a8a;
            color: #fff;
            border-color: #1e3a8a;
        }
        .pagination .page-item.disabled .page-link {
            opacity: .55;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                Meals
                <span style="font-size: 14px; font-weight: normal; color: #718096;">
                    ({{ $allCount }} total)
                </span>
            </h2>
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('admin.meals.create') }}" 
                   style="padding: 8px 16px; background: #48bb78; color: white; border: none; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
                    <span>+</span> Add Meal
                </a>
                <a href="{{ route('admin.meals.import.form') }}"
                   style="padding: 8px 16px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Bulk Import
                </a>
                <a href="{{ route('admin.meals.export.page') }}"
                   style="padding: 8px 16px; background: #38b2ac; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Bulk Export
                </a>
                
                <!-- Status Tabs -->
                <div style="display: flex; gap: 4px; background: #f7fafc; padding: 4px; border-radius: 6px;">
                    <a href="{{ route('admin.meals.index', ['status' => 'all']) }}" 
                       class="status-tab {{ $status == 'all' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        All ({{ $allCount }})
                    </a>
                    <a href="{{ route('admin.meals.index', ['status' => 'active']) }}" 
                       class="status-tab {{ $status == 'active' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        Active ({{ $activeCount }})
                    </a>
                    <a href="{{ route('admin.meals.index', ['status' => 'inactive']) }}" 
                       class="status-tab {{ $status == 'inactive' ? 'status-tab--active' : 'status-tab--inactive' }}">
                        Inactive ({{ $inactiveCount }})
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="padding: 16px; background: #f7fafc; border-bottom: 1px solid #e2e8f0;">
            <form action="{{ route('admin.meals.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
                <input type="hidden" name="status" value="{{ $status }}">
                
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Search</label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search meals..." 
                           style="width: 100%; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                </div>
                
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Restaurant</label>
                    <select name="restaurant_id" 
                            style="width: 100%; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Restaurants</option>
                        @foreach($restaurants as $restaurant)
                            <option value="{{ $restaurant->id }}" {{ request('restaurant_id') == $restaurant->id ? 'selected' : '' }}>
                                {{ $restaurant->restaurant_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #4a5568;">Meal Type</label>
                    <select name="meal_type" 
                            style="width: 100%; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Meal Types</option>
                        @foreach($mealTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('meal_type') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div style="display: flex; gap: 8px;">
                    <button type="submit" 
                            style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        Filter
                    </button>
                    @if(request('search') || request('restaurant_id') || request('meal_type'))
                        <a href="{{ route('admin.meals.index', ['status' => $status]) }}" 
                           style="padding: 8px 16px; background: #e2e8f0; color: #2d3748; border-radius: 6px; text-decoration: none; font-size: 14px; display: inline-block;">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @if($meals->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Restaurant</th>
                        <th>Meal Type</th>
                        <th>Menu Description</th>
                        <th>Price (EUR)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($meals as $meal)
                        <tr>
                            <td>{{ $meal->id }}</td>
                            <td>
                                <strong>{{ $meal->restaurant->restaurant_name }}</strong>
                                @if($meal->restaurant->city)
                                    <br><small style="color: #718096;">📍 {{ $meal->restaurant->city }}</small>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $meal->meal_type_label }}</strong>
                            </td>
                            <td>
                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                    {{ \Illuminate\Support\Str::limit($meal->menu_description, 100) }}
                                </div>
                            </td>
                            <td>
                                @if($meal->price)
                                    <strong style="color: #48bb78;">{{ $meal->price_eur_formatted }}</strong>
                                @else
                                    <span style="color: #718096;">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($meal->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="{{ route('admin.meals.edit', $meal->id) }}" 
                                       style="padding: 6px 12px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                        Edit
                                    </a>
                                    @if(!$meal->is_shared_template)
                                        <form action="{{ route('admin.meals.destroy', $meal->id) }}" 
                                              method="POST" 
                                              style="display: inline;"
                                              onsubmit="return confirm('Are you sure you want to delete this meal? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    style="padding: 6px 12px; background: #e53e3e; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top: 20px; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                <p style="font-size: 14px; color: #4a5568; margin: 0;">
                    Showing {{ $meals->firstItem() }} to {{ $meals->lastItem() }} of {{ $meals->total() }} results
                </p>
                {{ $meals->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">🍽️</div>
                <p>No meals found.</p>
                <a href="{{ route('admin.meals.create') }}" 
                   style="margin-top: 16px; display: inline-block; background: #667eea; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                    Create First Meal
                </a>
            </div>
        @endif
    </div>
@endsection


