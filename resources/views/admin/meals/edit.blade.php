@extends('admin.layouts.app')

@section('title', 'Edit Meal')
@section('page-title', 'Edit Meal')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Edit Meal</h2>
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

        <form action="{{ route('admin.meals.update', $meal->id) }}" method="POST" style="max-width: 1200px; padding: 20px;">
            @csrf
            @method('PUT')

            @if($meal->is_shared_template)
                <div style="margin-bottom: 24px; padding: 14px; background: #ebf8ff; border: 1px solid #90cdf4; border-radius: 8px; color: #2c5282; font-size: 14px;">
                    This row is tied to <strong>Global menu</strong>. Menu description, supplement wording, availability, and status are updated from
                    <a href="{{ route('admin.meal-templates.index') }}" style="color: #2b6cb0;">Global menu</a>.
                    You can only change meal and supplement <strong>prices</strong> here (or via restaurant / meal import).
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Menu description (read-only)</label>
                    <textarea rows="4" readonly disabled
                              style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; background: #f7fafc;">{{ $meal->menu_description }}</textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Price (EUR)</label>
                        <input type="number" name="price" value="{{ old('price', $meal->price) }}" step="0.01" min="0"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('price')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Currency</label>
                        <input type="text" value="EUR" disabled
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background-color:#edf2f7;">
                    </div>
                </div>

                @php $gsup = $meal->supplements ?? []; @endphp
                <div style="margin-bottom: 24px;">
                    <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">Supplements (read-only — edit under Global menu)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                            <h4 style="margin-bottom: 8px; color: #2d3748;">Starter supplement</h4>
                            <p style="font-size: 14px; color: #4a5568; margin-bottom: 8px;">
                                <strong>Offered:</strong> {{ !empty($gsup['starter']['available']) ? 'Yes' : 'No' }}
                            </p>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #4a5568; font-size: 13px;">Food items to include (supplement)</label>
                            <textarea rows="4" readonly disabled
                                      style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f7fafc;">{{ $gsup['starter']['description'] ?? '' }}</textarea>
                        </div>
                        <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                            <h4 style="margin-bottom: 8px; color: #2d3748;">Main course supplement</h4>
                            <p style="font-size: 14px; color: #4a5568; margin-bottom: 8px;">
                                <strong>Offered:</strong> {{ !empty($gsup['main_course']['available']) ? 'Yes' : 'No' }}
                            </p>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #4a5568; font-size: 13px;">Food items to include (supplement)</label>
                            <textarea rows="4" readonly disabled
                                      style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f7fafc;">{{ $gsup['main_course']['description'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px;">
                    <a href="{{ route('admin.meals.index') }}"
                       style="padding: 12px 24px; background: #e2e8f0; color: #2d3748; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500;">
                        Cancel
                    </a>
                    <button type="submit"
                            style="padding: 12px 24px; background: #4299e1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                        Update prices
                    </button>
                </div>
            @else
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
                                <option value="{{ $restaurant->id }}" {{ old('restaurant_id', $meal->restaurant_id) == $restaurant->id ? 'selected' : '' }}>
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
                        <input type="text"
                               name="meal_type"
                               id="meal_type"
                               list="meal-type-suggestions"
                               required
                               value="{{ old('meal_type', $meal->meal_type_label) }}"
                               placeholder="Select or type a meal type"
                               autocomplete="off"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <datalist id="meal-type-suggestions">
                            @foreach($mealTypeOptions as $key => $label)
                                <option value="{{ $label }}">{{ $label }}</option>
                            @endforeach
                        </datalist>
                        <p style="font-size: 12px; color: #718096; margin-top: 6px; margin-bottom: 0;">
                            You can pick a suggestion or type a <strong>new</strong> name; new types are saved for next time. Standard Lunch/Dinner/Cocktail (global) types cannot be set here — use <a href="{{ route('admin.meal-templates.index') }}">Global menu</a> for those.
                        </p>
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
                              style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('menu_description', $meal->menu_description) }}</textarea>
                    @error('menu_description')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Price (EUR)
                        </label>
                        <input type="number" 
                               name="price" 
                               value="{{ old('price', $meal->price) }}"
                               step="0.01"
                               min="0"
                               placeholder="e.g., 1800"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        @error('price')
                            <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Currency
                        </label>
                        <input type="text" value="EUR" disabled
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
                            <option value="active" {{ old('status', $meal->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $meal->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                               value="{{ old('display_order', $meal->display_order) }}"
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
                                       {{ old('supplements.starter.available', $meal->supplements['starter']['available'] ?? false) ? 'checked' : '' }}
                                       onchange="var on=this.checked; document.getElementById('starter_price').disabled=!on; document.getElementById('starter_description').disabled=!on;">
                                <span>Available</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                                Price (EUR)
                            </label>
                            <input type="number" 
                                   id="starter_price"
                                   name="supplements[starter][price]" 
                                   value="{{ old('supplements.starter.price', $meal->supplements['starter']['price'] ?? '') }}"
                                   step="0.01"
                                   min="0"
                                   {{ old('supplements.starter.available', $meal->supplements['starter']['available'] ?? false) ? '' : 'disabled' }}
                                   placeholder="e.g., 200"
                                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        </div>
                        <div class="form-group" style="margin-top: 12px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                                Food items to include (supplement)
                            </label>
                            <textarea id="starter_description"
                                      name="supplements[starter][description]"
                                      rows="3"
                                      placeholder="e.g., Soup of the day, 2 pcs veg starter, green salad"
                                      {{ old('supplements.starter.available', $meal->supplements['starter']['available'] ?? false) ? '' : 'disabled' }}
                                      style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('supplements.starter.description', $meal->supplements['starter']['description'] ?? '') }}</textarea>
                            @error('supplements.starter.description')
                                <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                            @enderror
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
                                       {{ old('supplements.main_course.available', $meal->supplements['main_course']['available'] ?? false) ? 'checked' : '' }}
                                       onchange="var on=this.checked; document.getElementById('main_course_price').disabled=!on; document.getElementById('main_course_description').disabled=!on;">
                                <span>Available</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                                Price (EUR)
                            </label>
                            <input type="number" 
                                   id="main_course_price"
                                   name="supplements[main_course][price]" 
                                   value="{{ old('supplements.main_course.price', $meal->supplements['main_course']['price'] ?? '') }}"
                                   step="0.01"
                                   min="0"
                                   {{ old('supplements.main_course.available', $meal->supplements['main_course']['available'] ?? false) ? '' : 'disabled' }}
                                   placeholder="e.g., 300"
                                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        </div>
                        <div class="form-group" style="margin-top: 12px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                                Food items to include (supplement)
                            </label>
                            <textarea id="main_course_description"
                                      name="supplements[main_course][description]"
                                      rows="3"
                                      placeholder="e.g., Extra main: 1 paneer dish + 1 chicken dish, bread basket"
                                      {{ old('supplements.main_course.available', $meal->supplements['main_course']['available'] ?? false) ? '' : 'disabled' }}
                                      style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('supplements.main_course.description', $meal->supplements['main_course']['description'] ?? '') }}</textarea>
                            @error('supplements.main_course.description')
                                <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                            @enderror
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
                        style="padding: 12px 24px; background: #4299e1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                    Update Meal
                </button>
            </div>
            @endif
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var sel = document.getElementById('meal_type_select');
    var custom = document.getElementById('meal_type_custom');
    var hidden = document.getElementById('meal_type_value');
    if (!sel || !custom || !hidden) return;
    var form = sel.closest('form');

    function sync() {
        if (!sel.value) {
            hidden.value = '';
            return;
        }
        if (sel.value === '__custom__') {
            hidden.value = (custom.value || '').trim();
        } else {
            hidden.value = sel.value;
        }
    }

    sel.addEventListener('change', function () {
        custom.style.display = sel.value === '__custom__' ? 'block' : 'none';
        if (sel.value !== '__custom__') custom.value = '';
        sync();
    });
    custom.addEventListener('input', sync);
    form.addEventListener('submit', function (e) {
        sync();
        if (!hidden.value.trim()) {
            e.preventDefault();
            alert('Please select a meal type or enter a custom type under Other.');
        }
    });
    sync();
})();
</script>
@endpush


