<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MealTemplate;
use App\Services\GlobalMealSyncService;
use Illuminate\Http\Request;

class MealTemplateController extends Controller
{
    public function index()
    {
        $templates = MealTemplate::orderBy('display_order')->orderBy('meal_type')->get();

        return view('admin.meal-templates.index', compact('templates'));
    }

    public function edit(MealTemplate $mealTemplate)
    {
        return view('admin.meal-templates.edit', ['template' => $mealTemplate]);
    }

    public function update(Request $request, MealTemplate $mealTemplate)
    {
        $validated = $request->validate([
            'menu_description' => 'required|string',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0',
            'supplements.starter.available' => 'nullable|boolean',
            'supplements.main_course.available' => 'nullable|boolean',
            'supplements.starter.description' => 'nullable|string|max:10000',
            'supplements.main_course.description' => 'nullable|string|max:10000',
        ]);

        $starterAvail = $request->boolean('supplements.starter.available');
        $mainAvail = $request->boolean('supplements.main_course.available');

        $starterDesc = $request->input('supplements.starter.description');
        $mainDesc = $request->input('supplements.main_course.description');
        $starterDescNorm = ($starterDesc !== null && trim((string) $starterDesc) !== '') ? trim((string) $starterDesc) : null;
        $mainDescNorm = ($mainDesc !== null && trim((string) $mainDesc) !== '') ? trim((string) $mainDesc) : null;

        // Availability and item copy are global; supplement prices (EUR) are per restaurant (restaurant bulk import).
        $supplements = [
            'starter' => [
                'available' => $starterAvail,
                'price' => null,
                'description' => $starterDescNorm,
            ],
            'main_course' => [
                'available' => $mainAvail,
                'price' => null,
                'description' => $mainDescNorm,
            ],
        ];

        $mealTemplate->update([
            'menu_description' => $validated['menu_description'],
            'status' => $validated['status'],
            'display_order' => $validated['display_order'] ?? 0,
            'supplements' => $supplements,
        ]);

        app(GlobalMealSyncService::class)->propagateTemplateContentToAllMeals($mealTemplate);

        return redirect()
            ->route('admin.meal-templates.index')
            ->with('success', 'Global menu updated and applied to all restaurants using this meal.');
    }
}
