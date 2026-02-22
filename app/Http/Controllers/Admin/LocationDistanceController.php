<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LocationDistance;
use Illuminate\Http\Request;

class LocationDistanceController extends Controller
{
    public function index(Request $request)
    {
        $query = LocationDistance::query();
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('from_location', 'like', "%{$s}%")
                    ->orWhere('to_location', 'like', "%{$s}%");
            });
        }
        $distances = $query->orderBy('from_location')->orderBy('to_location')->paginate(20);
        return view('admin.location-distances.index', compact('distances'));
    }

    public function create()
    {
        return view('admin.location-distances.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_location' => 'required|string|max:255',
            'to_location' => 'required|string|max:255',
            'distance_km' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);
        $from = trim($validated['from_location']);
        $to = trim($validated['to_location']);
        if (strcasecmp($from, $to) === 0) {
            return back()->withInput()->with('error', 'From and To locations must be different.');
        }
        $exists = LocationDistance::where('from_location', $from)->where('to_location', $to)->exists()
            || LocationDistance::where('from_location', $to)->where('to_location', $from)->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'This route already exists.');
        }
        LocationDistance::create([
            'from_location' => $from,
            'to_location' => $to,
            'distance_km' => $validated['distance_km'],
            'notes' => $validated['notes'] ?? null,
        ]);
        return redirect()->route('admin.location-distances.index')->with('success', 'Distance added.');
    }

    public function edit(string $id)
    {
        $distance = LocationDistance::findOrFail($id);
        return view('admin.location-distances.edit', compact('distance'));
    }

    public function update(Request $request, string $id)
    {
        $distance = LocationDistance::findOrFail($id);
        $validated = $request->validate([
            'from_location' => 'required|string|max:255',
            'to_location' => 'required|string|max:255',
            'distance_km' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);
        $from = trim($validated['from_location']);
        $to = trim($validated['to_location']);
        if (strcasecmp($from, $to) === 0) {
            return back()->withInput()->with('error', 'From and To locations must be different.');
        }
        $distance->update([
            'from_location' => $from,
            'to_location' => $to,
            'distance_km' => $validated['distance_km'],
            'notes' => $validated['notes'] ?? null,
        ]);
        return redirect()->route('admin.location-distances.index')->with('success', 'Distance updated.');
    }

    public function destroy(string $id)
    {
        $distance = LocationDistance::findOrFail($id);
        $distance->delete();
        return redirect()->route('admin.location-distances.index')->with('success', 'Distance removed.');
    }
}
