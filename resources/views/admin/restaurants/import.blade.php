@extends('admin.layouts.app')

@section('title', 'Bulk Import Restaurants')
@section('page-title', 'Bulk Import Restaurants')

@section('content')
    <style>
        @media (max-width: 900px) {
            .restaurant-import-layout {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    <div class="card">
        <div class="card-header" style="align-items: flex-start;">
            <div>
                <h2 class="card-title">Upload File</h2>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="{{ route('admin.restaurants.import.sample', ['format' => 'csv']) }}"
                   style="padding: 8px 12px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Download CSV Sample
                </a>
                <a href="{{ route('admin.restaurants.import.sample', ['format' => 'xlsx']) }}"
                   style="padding: 8px 12px; background: #667eea; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Download Excel Sample (.xlsx)
                </a>
                <a href="{{ route('admin.restaurants.export', ['format' => 'xls']) }}"
                   style="padding: 8px 12px; background: #48bb78; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Export All (Excel)
                </a>
                <a href="{{ route('admin.restaurants.export', ['format' => 'csv']) }}"
                   style="padding: 8px 12px; background: #38b2ac; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
                    Export All (CSV)
                </a>
            </div>
        </div>

        <div style="padding: 20px; display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(300px, 1fr); gap: 20px; align-items: start;">
            <form action="{{ route('admin.restaurants.import') }}" method="POST" enctype="multipart/form-data" style="background: #f7fafc; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="font-weight: 600; color: #2d3748;">Choose file</label>
                    <input type="file" name="file" accept=".csv,.xlsx"
                           style="padding: 10px; border: 2px dashed #cbd5e0; border-radius: 8px; background: white;">
                    @error('file')
                        <div style="color: #e53e3e; font-size: 14px;">{{ $message }}</div>
                    @enderror
                    <button type="submit"
                            style="padding: 10px 14px; background: #1e3a8a; color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer;">
                        Import Restaurants
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

            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; min-width: 0;">
                <h3 style="margin-bottom: 10px; color: #2d3748;">Template &amp; columns</h3>
                <p style="color: #4a5568; font-size: 14px; margin-bottom: 10px;">
                    Required: <strong>restaurant_name</strong>, <strong>address</strong>, <strong>phone</strong>, <strong>status</strong> (active or inactive).
                    Use Yes/No or 1/0 for boolean fields.
                </p>
                <p style="margin-bottom: 12px; padding: 12px; background: #f0fff4; border: 1px solid #9ae6b4; border-radius: 8px; font-size: 13px; color: #22543d;">
                    <strong>Files:</strong> upload <strong>.csv</strong> or <strong>.xlsx</strong> only. <strong>.xlsx</strong> is read with PhpSpreadsheet when available (run <code>composer install</code> so <code>vendor/phpoffice/phpspreadsheet</code> loads); otherwise a built-in reader is used.
                </p>
                <p style="color: #4a5568; font-size: 13px; margin-bottom: 10px;">
                    <strong>Images:</strong> put <strong>URLs only</strong> in the <code>images</code> column (or <code>image</code> / <code>photo</code> / <code>image_url</code>), separated by <strong>|</strong>, comma, semicolon, or newline. URLs from <a href="{{ route('admin.media-library.index') }}" style="color: #2b6cb0;">Media library</a> are stored as paths automatically. Embedded pictures in cells are not imported.
                </p>
                <p style="color: #4a5568; font-size: 13px; margin-bottom: 10px;">
                    <strong>Sample file rows:</strong> (1) all restaurant fields + all global meal prices and supplements;
                    (2) required columns only; (3) restaurant + main EUR prices for the four global meal plans, supplements optional;
                    (4) restaurant + mixed meal/supplement cells. You may omit optional columns from your own sheet if you do not need them.
                </p>
                <div style="margin-bottom: 14px; padding: 12px; background: #ebf8ff; border: 1px solid #90cdf4; border-radius: 8px; font-size: 13px; color: #2c5282;">
                    <strong>Global meal tail (optional):</strong> after the restaurant columns, add groups of three columns per plan—meal price, starter supplement EUR, main-course supplement EUR.
                    Menu wording is <strong>not</strong> imported here; it is set under <a href="{{ route('admin.meal-templates.index') }}" style="color: #2b6cb0;">Global menu</a>.
                    <p style="margin: 10px 0 0; font-size: 12px; color: #2a4365;">
                        Legacy sheets with <code>meal_price_premium_buffet_lunch</code> (old global) are not applied—<code>premium_buffet_lunch</code> is now a normal per-restaurant meal if you add it under Meals.
                    </p>
                </div>
                @if(!empty($globalMealColumnGroups))
                    <div style="margin-bottom: 14px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px; border: 1px solid #e2e8f0;">
                            <thead>
                                <tr style="background: #edf2f7; text-align: left;">
                                    <th style="padding: 8px; border-bottom: 1px solid #e2e8f0;">Plan</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #e2e8f0;"><code>meal_price_*</code></th>
                                    <th style="padding: 8px; border-bottom: 1px solid #e2e8f0;">Starter supp.</th>
                                    <th style="padding: 8px; border-bottom: 1px solid #e2e8f0;">Main supp.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($globalMealColumnGroups as $g)
                                    <tr style="border-bottom: 1px solid #edf2f7;">
                                        <td style="padding: 8px; vertical-align: top;">{{ $g['label'] }}<br><span style="color: #718096;"><code style="font-size: 11px;">{{ $g['type'] }}</code></span></td>
                                        <td style="padding: 8px; vertical-align: top;"><code style="word-break: break-all;">{{ $g['price_column'] }}</code></td>
                                        <td style="padding: 8px; vertical-align: top;"><code style="word-break: break-all;">{{ $g['supplement_starter_column'] }}</code></td>
                                        <td style="padding: 8px; vertical-align: top;"><code style="word-break: break-all;">{{ $g['supplement_main_course_column'] }}</code></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div style="max-height: min(70vh, 520px); overflow-y: auto; padding-right: 4px; border-top: 1px solid #edf2f7; margin-top: 8px; padding-top: 12px;">
                    <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px;">
                        @foreach($columns as $column)
                            <li style="margin: 0; padding: 8px 10px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; line-height: 1.45; word-break: break-word; overflow-wrap: anywhere;">
                                <code style="font-size: 12px; color: #2d3748;">{{ $column }}</code>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
