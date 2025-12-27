@extends('admin.layouts.app')

@section('title', 'Meal Details')
@section('page-title', 'Meal Details')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Meal Details</h2>
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('admin.meals.index') }}" 
                   style="color: #667eea; text-decoration: none; font-size: 14px;">
                    ← Back to Meals List
                </a>
                <a href="{{ route('admin.meals.edit', $meal->id) }}" 
                   style="padding: 8px 16px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Edit Meal
                </a>
            </div>
        </div>

        <div style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <div>
                    <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Basic Information</h3>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Restaurant</label>
                        <p style="color: #2d3748; font-size: 16px;">
                            <strong>{{ $meal->restaurant->restaurant_name }}</strong>
                            @if($meal->restaurant->city)
                                <br><small style="color: #718096;">📍 {{ $meal->restaurant->city }}</small>
                            @endif
                        </p>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Meal Type</label>
                        <p style="color: #2d3748; font-size: 16px;">
                            <strong>{{ $meal->meal_type_label }}</strong>
                        </p>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Status</label>
                        <p>
                            @if($meal->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </p>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Display Order</label>
                        <p style="color: #2d3748; font-size: 16px;">{{ $meal->display_order }}</p>
                    </div>
                </div>

                <div>
                    <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Pricing</h3>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Price (INR)</label>
                        <p style="color: #2d3748; font-size: 18px; font-weight: 600;">
                            @if($meal->price_inr)
                                <span style="color: #48bb78;">{{ $meal->price_inr_formatted }}</span>
                            @else
                                <span style="color: #718096;">Not set</span>
                            @endif
                        </p>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Local Currency</label>
                        <p style="color: #2d3748; font-size: 16px;">
                            @if($meal->local_currency)
                                {{ $meal->local_currency }}
                            @else
                                <span style="color: #718096;">Not set</span>
                            @endif
                        </p>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Local Price</label>
                        <p style="color: #2d3748; font-size: 18px; font-weight: 600;">
                            @if($meal->local_price_formatted)
                                <span style="color: #667eea;">{{ $meal->local_price_formatted }}</span>
                            @else
                                <span style="color: #718096;">Not set</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Menu Description</h3>
                <p style="color: #2d3748; font-size: 16px; line-height: 1.6; white-space: pre-wrap;">{{ $meal->menu_description }}</p>
            </div>

            @if($meal->supplements && (isset($meal->supplements['starter']) || isset($meal->supplements['main_course'])))
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Supplements</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        @if(isset($meal->supplements['starter']) && $meal->supplements['starter']['available'])
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                                <h4 style="margin-bottom: 8px; color: #2d3748;">Starter Supplement</h4>
                                <p style="color: #48bb78; font-size: 18px; font-weight: 600;">
                                    ₹{{ number_format($meal->supplements['starter']['price'] ?? 0, 2) }}
                                </p>
                            </div>
                        @endif

                        @if(isset($meal->supplements['main_course']) && $meal->supplements['main_course']['available'])
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                                <h4 style="margin-bottom: 8px; color: #2d3748;">Main Course Supplement</h4>
                                <p style="color: #48bb78; font-size: 18px; font-weight: 600;">
                                    ₹{{ number_format($meal->supplements['main_course']['price'] ?? 0, 2) }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                <small style="color: #718096;">
                    Created: {{ $meal->created_at->format('M d, Y h:i A') }}<br>
                    Last Updated: {{ $meal->updated_at->format('M d, Y h:i A') }}
                </small>
            </div>
        </div>
    </div>
@endsection


