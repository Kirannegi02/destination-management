@extends('admin.layouts.app')

@section('title', 'Bulk Export Restaurants')
@section('page-title', 'Bulk Export Restaurants')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Export Stores</h2>
        </div>

        <div style="padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 20px;">
            @if(!empty($globalMealColumnGroups))
                <div style="width: 100%; max-width: 720px; padding: 14px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; color: #2d3748;">
                    <strong>Export includes global meal columns</strong> (same names as restaurant import): for each plan below, the file has price plus starter and main-course supplement amounts when set.
                    <div style="overflow-x: auto; margin-top: 10px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead>
                                <tr style="background: #edf2f7; text-align: left;">
                                    <th style="padding: 6px 8px; border: 1px solid #e2e8f0;">Plan</th>
                                    <th style="padding: 6px 8px; border: 1px solid #e2e8f0;">Price column</th>
                                    <th style="padding: 6px 8px; border: 1px solid #e2e8f0;">Supplements</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($globalMealColumnGroups as $g)
                                    <tr>
                                        <td style="padding: 6px 8px; border: 1px solid #e2e8f0;">{{ $g['label'] }}</td>
                                        <td style="padding: 6px 8px; border: 1px solid #e2e8f0;"><code style="word-break: break-all;">{{ $g['price_column'] }}</code></td>
                                        <td style="padding: 6px 8px; border: 1px solid #e2e8f0;">
                                            <code style="word-break: break-all;">{{ $g['supplement_starter_column'] }}</code>,
                                            <code style="word-break: break-all;">{{ $g['supplement_main_course_column'] }}</code>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            <div style="width: 100%; max-width: 400px; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; background: #f8fafc;">
                <form action="{{ route('admin.restaurants.export') }}" method="GET" style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="font-weight: 600; color: #2d3748;">File format</label>
                    <select name="format" style="padding: 10px 12px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px;">
                        <option value="xls">Excel (.xls)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                    <button type="submit"
                            style="padding: 10px 14px; background: #1e3a8a; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer;">
                        Export All Data
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
