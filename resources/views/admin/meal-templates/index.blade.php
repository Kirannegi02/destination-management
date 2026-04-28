@extends('admin.layouts.app')

@section('title', 'Global menu (shared meals)')
@section('page-title', 'Global menu (shared meals)')

@section('content')
    <div class="card">
        <div class="card-header" style="align-items: flex-start;">
            <div>
                <h2 class="card-title">Global meal plans (all restaurants)</h2>
                <p style="color: #718096; font-size: 14px; margin-top: 4px;">
                    Menu text, <strong>which</strong> supplements are offered, and <strong>what food items</strong> each supplement includes apply to all restaurants. Meal and supplement <strong>prices</strong> are per restaurant—use <strong>Restaurants → Bulk Import</strong>
                    (<code>meal_price_*</code>, <code>meal_supplement_starter_*</code>, <code>meal_supplement_main_course_*</code>) or <strong>Meals → Bulk Import</strong>.
                </p>
            </div>
        </div>

        @if(session('success'))
            <div style="margin: 16px 20px; padding: 12px; background: #c6f6d5; border-radius: 8px; color: #22543d;">
                {{ session('success') }}
            </div>
        @endif

        <div style="padding: 20px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                        <th style="padding: 10px;">Meal type</th>
                        <th style="padding: 10px;">Label</th>
                        <th style="padding: 10px;">Status</th>
                        <th style="padding: 10px;">Order</th>
                        <th style="padding: 10px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $t)
                        @php $labels = \App\Models\Meal::getMealTypes(); @endphp
                        <tr style="border-bottom: 1px solid #edf2f7;">
                            <td style="padding: 10px;"><code>{{ $t->meal_type }}</code></td>
                            <td style="padding: 10px;">{{ $labels[$t->meal_type] ?? $t->meal_type }}</td>
                            <td style="padding: 10px;">{{ $t->status }}</td>
                            <td style="padding: 10px;">{{ $t->display_order }}</td>
                            <td style="padding: 10px;">
                                <a href="{{ route('admin.meal-templates.edit', $t) }}"
                                   style="color: #4299e1; text-decoration: none; font-weight: 600;">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
