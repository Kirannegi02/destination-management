<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'restaurant');
        
        // Validate service type
        $validTypes = array_keys(Service::getTypes());
        if (!in_array($type, $validTypes)) {
            return redirect()->route('admin.services.index', ['type' => 'restaurant']);
        }

        $services = Service::where('type', $type)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $typeLabel = Service::getTypes()[$type] ?? ucfirst($type);

        return view('admin.services.index', compact('services', 'type', 'typeLabel'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $type = $request->get('type', 'restaurant');
        
        // Validate service type
        $validTypes = array_keys(Service::getTypes());
        if (!in_array($type, $validTypes)) {
            return redirect()->route('admin.services.index', ['type' => 'restaurant']);
        }

        $typeLabel = Service::getTypes()[$type] ?? ucfirst($type);
        
        return view('admin.services.create', compact('type', 'typeLabel'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(Service::getTypes())),
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . time();
        $validated['currency'] = $validated['currency'] ?? 'INR';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Service::create($validated);

        return redirect()
            ->route('admin.services.index', ['type' => $validated['type']])
            ->with('success', 'Service created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $service = Service::findOrFail($id);
        $typeLabel = $service->type_label;
        
        return view('admin.services.show', compact('service', 'typeLabel'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $service = Service::findOrFail($id);
        $typeLabel = $service->type_label;
        
        return view('admin.services.edit', compact('service', 'typeLabel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $service->update($validated);

        return redirect()
            ->route('admin.services.index', ['type' => $service->type])
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $service = Service::findOrFail($id);
        $type = $service->type;
        
        $service->delete();

        return redirect()
            ->route('admin.services.index', ['type' => $type])
            ->with('success', 'Service deleted successfully.');
    }
}
