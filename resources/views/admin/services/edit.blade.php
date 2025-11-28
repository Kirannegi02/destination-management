@extends('admin.layouts.app')

@section('title', 'Edit ' . $typeLabel)
@section('page-title', 'Edit ' . $typeLabel)

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Edit {{ $typeLabel }}</h2>
            <a href="{{ route('admin.services.index', ['type' => $service->type]) }}" 
               style="color: #667eea; text-decoration: none; font-size: 14px;">
                ← Back to List
            </a>
        </div>

        <form action="{{ route('admin.services.update', $service->id) }}" method="POST" style="max-width: 800px;">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Name <span style="color: #e53e3e;">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           value="{{ old('name', $service->name) }}" 
                           required
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('name')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Status <span style="color: #e53e3e;">*</span>
                    </label>
                    <select name="status" 
                            required
                            style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="active" {{ old('status', $service->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $service->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                    Description
                </label>
                <textarea name="description" 
                          rows="4"
                          style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('description', $service->description) }}</textarea>
                @error('description')
                    <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Location
                    </label>
                    <input type="text" 
                           name="location" 
                           value="{{ old('location', $service->location) }}"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('location')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        City
                    </label>
                    <input type="text" 
                           name="city" 
                           value="{{ old('city', $service->city) }}"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('city')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Country
                    </label>
                    <input type="text" 
                           name="country" 
                           value="{{ old('country', $service->country) }}"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('country')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Price
                    </label>
                    <input type="number" 
                           name="price" 
                           value="{{ old('price', $service->price) }}" 
                           step="0.01"
                           min="0"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('price')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Currency
                    </label>
                    <select name="currency" 
                            style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="INR" {{ old('currency', $service->currency) == 'INR' ? 'selected' : '' }}>INR</option>
                        <option value="USD" {{ old('currency', $service->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="EUR" {{ old('currency', $service->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                    </select>
                    @error('currency')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Capacity
                    </label>
                    <input type="number" 
                           name="capacity" 
                           value="{{ old('capacity', $service->capacity) }}" 
                           min="1"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('capacity')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                        Sort Order
                    </label>
                    <input type="number" 
                           name="sort_order" 
                           value="{{ old('sort_order', $service->sort_order) }}" 
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    @error('sort_order')
                        <div style="color: #e53e3e; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="submit" 
                        style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    Update {{ $typeLabel }}
                </button>
                <a href="{{ route('admin.services.index', ['type' => $service->type]) }}" 
                   style="background: #e2e8f0; color: #2d3748; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-block;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection



