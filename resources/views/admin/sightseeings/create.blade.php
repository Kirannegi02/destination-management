@extends('admin.layouts.app')

@section('title', 'Add Sightseeing')
@section('page-title', 'Add Sightseeing')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Add New Sightseeing</h2>
            <a href="{{ route('admin.sightseeings.index') }}" 
               style="color: #667eea; text-decoration: none; font-size: 14px;">
                ← Back to Sightseeings
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

        <form action="{{ route('admin.sightseeings.store') }}" method="POST" enctype="multipart/form-data" style="max-width: 1200px; padding: 20px;">
            @csrf

            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Basics & Location</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Sightseeing Title <span style="color: #e53e3e;">*</span>
                        </label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">
                            Status <span style="color: #e53e3e;">*</span>
                        </label>
                        <select name="status" required
                                style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Country</label>
                        <input type="text" name="country" value="{{ old('country', 'Switzerland') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">City</label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Start Location</label>
                        <input type="text" name="start_location" value="{{ old('start_location') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">End Location</label>
                        <input type="text" name="end_location" value="{{ old('end_location') }}"
                               style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display:flex; align-items:center; gap:8px; font-weight:600; color:#2d3748; cursor:pointer;">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} style="width:18px; height:18px; cursor:pointer;">
                    Feature this experience
                </label>
            </div>

            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Content</h3>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Description</label>
                    <textarea name="description" rows="3"
                              style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">{{ old('description') }}</textarea>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">Cover Image</h3>
                <div class="form-group">
                    <input type="file" name="image" accept="image/*"
                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <small style="color:#718096; font-size:12px; display:block; margin-top:4px;">Optional cover image (max 4MB).</small>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <h3 style="color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px;">Sightseeing Options / Packages</h3>
                <p style="color:#4a5568; margin-bottom:12px;">Match the options from the spreadsheet (Mt. Titlis variations, Rhine Falls boat durations, Lindt museum, etc.).</p>
                <div style="overflow-x:auto;">
                    <table class="table" id="options-table" style="min-width: 900px;">
                        <thead>
                            <tr>
                                <th>Option Name</th>
                                <th>Duration (mins)</th>
                                <th>Base Price</th>
                                <th>Includes Lunch</th>
                                <th>Includes Transport</th>
                                <th>Tags</th>
                                <th>Active?</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $oldOptions = old('options', $sampleOptions);
                            @endphp
                            @foreach($oldOptions as $idx => $opt)
                                <tr>
                                    <td>
                                        <input type="text" name="options[{{ $idx }}][name]" value="{{ $opt['name'] ?? '' }}"
                                               style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">
                                        <textarea name="options[{{ $idx }}][description]" placeholder="Description (optional)" rows="2"
                                                  style="width: 100%; margin-top: 6px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">{{ $opt['description'] ?? '' }}</textarea>
                                    </td>
                                    <td><input type="number" min="0" name="options[{{ $idx }}][duration_minutes]" value="{{ $opt['duration_minutes'] ?? '' }}"
                                               style="width: 120px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
                                    <td><input type="number" step="0.01" min="0" name="options[{{ $idx }}][base_price]" value="{{ $opt['base_price'] ?? '' }}"
                                               style="width: 140px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="options[{{ $idx }}][includes_lunch]" value="1" {{ !empty($opt['includes_lunch']) ? 'checked' : '' }} style="width:18px; height:18px;">
                                    </td>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="options[{{ $idx }}][includes_transport]" value="1" {{ !empty($opt['includes_transport']) ? 'checked' : '' }} style="width:18px; height:18px;">
                                    </td>
                                    <td>
                                        <input type="text" name="options[{{ $idx }}][tags]" value="{{ is_array($opt['tags'] ?? null) ? implode(',', $opt['tags']) : ($opt['tags'] ?? '') }}"
                                               placeholder="comma separated" style="width: 160px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">
                                        <textarea name="options[{{ $idx }}][availability_note]" placeholder="Availability note" rows="2"
                                                  style="width: 100%; margin-top: 6px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">{{ $opt['availability_note'] ?? '' }}</textarea>
                                    </td>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="options[{{ $idx }}][is_active]" value="1" {{ array_key_exists('is_active', $opt) ? ($opt['is_active'] ? 'checked' : '') : 'checked' }} style="width:18px; height:18px;">
                                    </td>
                                    <td><button type="button" class="remove-option" style="background:#e53e3e; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;">✕</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" id="add-option" style="margin-top: 12px; background: #667eea; color: white; padding: 10px 16px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                    + Add another option
                </button>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="submit" 
                        style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    Create Sightseeing
                </button>
                <a href="{{ route('admin.sightseeings.index') }}" 
                   style="background: #e2e8f0; color: #2d3748; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-block;">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        (function() {
            const tableBody = document.querySelector('#options-table tbody');
            const addBtn = document.getElementById('add-option');

            function addRow(data = {}) {
                const index = tableBody.querySelectorAll('tr').length;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="text" name="options[${index}][name]" value="${data.name || ''}"
                               style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">
                        <textarea name="options[${index}][description]" placeholder="Description (optional)" rows="2"
                                  style="width: 100%; margin-top: 6px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">${data.description || ''}</textarea>
                    </td>
                    <td><input type="number" min="0" name="options[${index}][duration_minutes]" value="${data.duration_minutes ?? ''}"
                               style="width: 120px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
                    <td><input type="number" step="0.01" min="0" name="options[${index}][base_price]" value="${data.base_price ?? ''}"
                               style="width: 140px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
                    <td style="text-align:center;">
                        <input type="checkbox" name="options[${index}][includes_lunch]" value="1" ${data.includes_lunch ? 'checked' : ''} style="width:18px; height:18px;">
                    </td>
                    <td style="text-align:center;">
                        <input type="checkbox" name="options[${index}][includes_transport]" value="1" ${data.includes_transport ? 'checked' : ''} style="width:18px; height:18px;">
                    </td>
                    <td>
                        <input type="text" name="options[${index}][tags]" value="${data.tags || ''}"
                               placeholder="comma separated" style="width: 160px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">
                        <textarea name="options[${index}][availability_note]" placeholder="Availability note" rows="2"
                                  style="width: 100%; margin-top: 6px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">${data.availability_note || ''}</textarea>
                    </td>
                    <td style="text-align:center;">
                        <input type="checkbox" name="options[${index}][is_active]" value="1" ${data.is_active === false ? '' : 'checked'} style="width:18px; height:18px;">
                    </td>
                    <td><button type="button" class="remove-option" style="background:#e53e3e; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;">✕</button></td>
                `;
                tableBody.appendChild(row);
            }

            addBtn.addEventListener('click', () => addRow());

            tableBody.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-option')) {
                    const row = e.target.closest('tr');
                    row.remove();
                }
            });
        })();
    </script>
@endsection

