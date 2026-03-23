@extends('admin.layouts.app')

@section('title', 'Add New Meal')
@section('page-title', 'Add New Meal')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Add New Meal</h2>
            <a href="{{ route('admin.meals.index') }}" 
               style="color: #667eea; text-decoration: none; font-size: 14px;">
                ← Back to Meals List
            </a>
        </div>

        @if($errors->any())
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin: 20px; border: 1px solid #f5c6cb;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 8px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin: 20px; border: 1px solid #f5c6cb;">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('admin.meals.store') }}" method="POST" style="max-width: 1200px; padding: 20px;">
            @csrf

            <!-- Basic Information Section -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Basic Information</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Restaurant <span style="color: #e53e3e;">*</span>
                        </label>
                        <select name="restaurant_id" 
                                required
                                style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">Select Restaurant</option>
                            @foreach($restaurants as $restaurant)
                                <option value="{{ $restaurant->id }}" {{ old('restaurant_id') == $restaurant->id ? 'selected' : '' }}>
                                    {{ $restaurant->restaurant_name }} 
                                    @if($restaurant->city)
                                        - {{ $restaurant->city }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('restaurant_id')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Meal Type <span style="color: #e53e3e;">*</span>
                        </label>
                        <select name="meal_type" 
                                required
                                style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">Select Meal Type</option>
                            @foreach($mealTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('meal_type') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('meal_type')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Menu Description <span style="color: #e53e3e;">*</span>
                    </label>
                    <textarea name="menu_description" 
                              rows="5"
                              required
                              placeholder="e.g., 2 Veg + 1 Non Veg, Dal, Rice, Roti, Salad, Condiments, 1 Indian Dessert"
                              style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('menu_description') }}</textarea>
                    @error('menu_description')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Price (INR)
                        </label>
                        <input type="number" 
                               name="price_inr" 
                               value="{{ old('price_inr') }}"
                               step="0.01"
                               min="0"
                               placeholder="e.g., 1800"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('price_inr')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Currency
                        </label>
                        <input type="text" value="INR" disabled
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background-color:#edf2f7;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Status <span style="color: #e53e3e;">*</span>
                        </label>
                        <select name="status" 
                                required
                                style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Display Order
                        </label>
                        <input type="number" 
                               name="display_order" 
                               value="{{ old('display_order', 0) }}"
                               min="0"
                               placeholder="0"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('display_order')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Supplements Section -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Supplements</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Starter Supplement -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                        <h4 style="margin-bottom: 12px; color: #2d3748;">Starter Supplement</h4>
                        
                        <div style="margin-bottom: 12px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" 
                                       name="supplements[starter][available]" 
                                       value="1"
                                       {{ old('supplements.starter.available') ? 'checked' : '' }}
                                       onchange="document.getElementById('starter_price').disabled = !this.checked;">
                                <span>Available</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                                Price (INR)
                            </label>
                            <input type="number" 
                                   id="starter_price"
                                   name="supplements[starter][price]" 
                                   value="{{ old('supplements.starter.price') }}"
                                   step="0.01"
                                   min="0"
                                   {{ old('supplements.starter.available') ? '' : 'disabled' }}
                                   placeholder="e.g., 200"
                                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        </div>
                    </div>

                    <!-- Main Course Supplement -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                        <h4 style="margin-bottom: 12px; color: #2d3748;">Main Course Supplement</h4>
                        
                        <div style="margin-bottom: 12px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" 
                                       name="supplements[main_course][available]" 
                                       value="1"
                                       {{ old('supplements.main_course.available') ? 'checked' : '' }}
                                       onchange="document.getElementById('main_course_price').disabled = !this.checked;">
                                <span>Available</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                                Price (INR)
                            </label>
                            <input type="number" 
                                   id="main_course_price"
                                   name="supplements[main_course][price]" 
                                   value="{{ old('supplements.main_course.price') }}"
                                   step="0.01"
                                   min="0"
                                   {{ old('supplements.main_course.available') ? '' : 'disabled' }}
                                   placeholder="e.g., 300"
                                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px;">
                <a href="{{ route('admin.meals.index') }}" 
                   style="padding: 12px 24px; background: #e2e8f0; color: #2d3748; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500;">
                    Cancel
                </a>
                <button type="submit" 
                        style="padding: 12px 24px; background: #48bb78; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                    Create Meal
                </button>
            </div>
        </form>
    </div>
@endsection


