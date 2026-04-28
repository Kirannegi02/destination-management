@extends('admin.layouts.app')

@section('title', 'Edit global meal')
@section('page-title', 'Edit global meal')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                @php $labels = \App\Models\Meal::getMealTypes(); @endphp
                {{ $labels[$template->meal_type] ?? $template->meal_type }}
            </h2>
            <a href="{{ route('admin.meal-templates.index') }}" style="color: #667eea; font-size: 14px;">← Back</a>
        </div>

        @if(session('error'))
            <div style="margin: 16px 20px; padding: 12px; background: #fed7d7; border-radius: 8px; color: #742a2a;">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.meal-templates.update', $template) }}" style="padding: 20px; max-width: 900px;">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Menu description (all restaurants)</label>
                <textarea name="menu_description" rows="6" required
                          style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">{{ old('menu_description', $template->menu_description) }}</textarea>
                @error('menu_description')
                    <div style="color: #e53e3e; font-size: 13px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                        <option value="active" @selected(old('status', $template->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $template->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Display order</label>
                    <input type="number" name="display_order" min="0" value="{{ old('display_order', $template->display_order) }}"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
            </div>

            @php
                $sup = $template->supplements ?? [];
                $stAvail = old('supplements.starter.available', $sup['starter']['available'] ?? false);
                $mcAvail = old('supplements.main_course.available', $sup['main_course']['available'] ?? false);
                $stDesc = old('supplements.starter.description', $sup['starter']['description'] ?? '');
                $mcDesc = old('supplements.main_course.description', $sup['main_course']['description'] ?? '');
            @endphp

            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Supplements</h3>
                <p style="color: #718096; font-size: 14px; margin-bottom: 16px;">
                    Turn options on or off for <strong>all restaurants</strong>, and describe <strong>which food items</strong> count as each supplement. Supplement <strong>prices (EUR) are not set here</strong>—each restaurant sets them via
                    <strong>Restaurants → Bulk Import</strong> using columns <code>meal_supplement_starter_*</code> and <code>meal_supplement_main_course_*</code>.
                </p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                        <h4 style="margin-bottom: 12px; color: #2d3748;">Starter Supplement</h4>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 12px;">
                            <input type="checkbox"
                                   name="supplements[starter][available]"
                                   value="1"
                                   {{ $stAvail ? 'checked' : '' }}>
                            <span>Offer starter supplement (price per restaurant on import)</span>
                        </label>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #2d3748; font-size: 14px;">Food items to include (supplement)</label>
                        <textarea name="supplements[starter][description]" rows="4"
                                  placeholder="e.g., Soup of the day, 2 pcs veg starter, green salad"
                                  style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ $stDesc }}</textarea>
                        @error('supplements.starter.description')
                            <div style="color: #e53e3e; font-size: 13px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                        <h4 style="margin-bottom: 12px; color: #2d3748;">Main Course Supplement</h4>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 12px;">
                            <input type="checkbox"
                                   name="supplements[main_course][available]"
                                   value="1"
                                   {{ $mcAvail ? 'checked' : '' }}>
                            <span>Offer main-course supplement (price per restaurant on import)</span>
                        </label>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #2d3748; font-size: 14px;">Food items to include (supplement)</label>
                        <textarea name="supplements[main_course][description]" rows="4"
                                  placeholder="e.g., Extra main: 1 paneer dish + 1 chicken dish, bread basket"
                                  style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ $mcDesc }}</textarea>
                        @error('supplements.main_course.description')
                            <div style="color: #e53e3e; font-size: 13px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <button type="submit" style="padding: 12px 20px; background: #4299e1; color: white; border: none; border-radius: 8px; cursor: pointer;">
                Save and sync to all restaurants
            </button>
        </form>
    </div>
@endsection
