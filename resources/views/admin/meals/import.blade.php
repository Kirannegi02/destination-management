@extends('admin.layouts.app')

@section('title', 'Bulk Import Meals')
@section('page-title', 'Bulk Import Meals')

@section('content')
    <div class="card">
        <div class="card-header" style="align-items: flex-start;">
            <div>
                <h2 class="card-title">Upload File</h2>
                <p style="color: #718096; font-size: 14px; margin-top: 4px;">
                    CSV, XLS, or XLSX. For the four <strong>global</strong> meal types (Lunch, Dinner, and both Cocktail Dinner plans), leave <code>menu_description</code> empty — text comes from <strong>Global menu</strong>. Other meal types need a full description.
                </p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="{{ route('admin.meals.import.sample', ['format' => 'csv']) }}"
                   style="padding: 8px 12px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Download CSV Sample
                </a>
                <a href="{{ route('admin.meals.import.sample', ['format' => 'xls']) }}"
                   style="padding: 8px 12px; background: #667eea; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Download Excel Sample
                </a>
            </div>
        </div>

        <div style="padding: 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">
            <form action="{{ route('admin.meals.import') }}" method="POST" enctype="multipart/form-data" style="background: #f7fafc; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="font-weight: 600; color: #2d3748;">Choose file</label>
                    <input type="file" name="file" accept=".csv,.xls,.xlsx"
                           style="padding: 10px; border: 2px dashed #cbd5e0; border-radius: 8px; background: white;">
                    @error('file')
                        <div style="color: #e53e3e; font-size: 14px;">{{ $message }}</div>
                    @enderror
                    <button type="submit"
                            style="padding: 10px 14px; background: #1e3a8a; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer;">
                        Import Meals
                    </button>
                </div>
                @if(session('import_errors') && count(session('import_errors')))
                    <div style="margin-top: 16px; background: #fffaf0; padding: 12px; border-radius: 8px; border: 1px solid #fbd38d;">
                        <strong style="color: #c05621;">Some rows were skipped:</strong>
                        <ul style="margin-top: 8px; color: #744210; padding-left: 20px;">
                            @foreach(session('import_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </form>

            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                <h3 style="margin-bottom: 10px; color: #2d3748;">Columns</h3>
                <p style="color: #4a5568; font-size: 14px; margin-bottom: 10px;">
                    Required: <strong>restaurant_name</strong>, <strong>phone</strong> (must match a restaurant), <strong>meal_type</strong>.
                    <strong>menu_description</strong> required except for global types:
                    <code>standard_buffet_lunch</code>, <code>standard_buffet_dinner</code>, <code>cocktail_dinner_without_liquor</code>, <code>cocktail_dinner_with_liquor</code>.
                </p>
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px; color: #2d3748; font-size: 14px;">
                    @foreach($columns as $column)
                        <span>• {{ $column }}</span>
                    @endforeach
                </div>
                <p style="color: #4a5568; font-size: 13px; margin-top: 12px;">
                    Meal <strong>export</strong> adds an <code>is_shared_template</code> column for reference; you can remove that column before re-importing.
                </p>
            </div>
        </div>
    </div>
@endsection
