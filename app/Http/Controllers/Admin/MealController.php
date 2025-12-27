<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class MealController extends Controller
{
    /**
     * Display a listing of the meals.
     */
    public function index(Request $request)
    {
        $query = Meal::with('restaurant');

        // Filter by restaurant
        if ($request->has('restaurant_id') && $request->restaurant_id) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // Filter by meal type
        if ($request->has('meal_type') && $request->meal_type) {
            $query->where('meal_type', $request->meal_type);
        }

        // Filter by status
        $status = $request->get('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('menu_description', 'like', "%{$search}%")
                  ->orWhereHas('restaurant', function($restaurantQuery) use ($search) {
                      $restaurantQuery->where('restaurant_name', 'like', "%{$search}%");
                  });
            });
        }

        $meals = $query->orderBy('restaurant_id')->orderBy('display_order')->orderBy('created_at', 'desc')->paginate(15);

        // Get all restaurants for filter
        $restaurants = Restaurant::orderBy('restaurant_name')->get();

        // Get meal types
        $mealTypes = Meal::getMealTypes();

        // Get counts for status tabs
        $allCount = Meal::count();
        $activeCount = Meal::where('status', 'active')->count();
        $inactiveCount = Meal::where('status', 'inactive')->count();

        return view('admin.meals.index', compact(
            'meals',
            'restaurants',
            'mealTypes',
            'status',
            'allCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    /**
     * Show the form for creating a new meal.
     */
    public function create()
    {
        $restaurants = Restaurant::orderBy('restaurant_name')->get();
        $mealTypes = Meal::getMealTypes();

        return view('admin.meals.create', compact('restaurants', 'mealTypes'));
    }

    /**
     * Store a newly created meal in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'meal_type' => 'required|in:standard_buffet_lunch,standard_buffet_dinner,premium_buffet_lunch,premium_buffet_dinner,cocktail_dinner_without_liquor,cocktail_dinner_with_liquor',
            'menu_description' => 'required|string',
            'price_inr' => 'nullable|numeric|min:0',
            'local_currency' => 'nullable|string|max:10',
            'local_price' => 'nullable|numeric|min:0',
            'supplements' => 'nullable|array',
            'supplements.starter' => 'nullable|array',
            'supplements.starter.available' => 'nullable|boolean',
            'supplements.starter.price' => 'nullable|numeric|min:0',
            'supplements.main_course' => 'nullable|array',
            'supplements.main_course.available' => 'nullable|boolean',
            'supplements.main_course.price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0',
        ]);

        // Check if meal type already exists for this restaurant
        $existingMeal = Meal::where('restaurant_id', $validated['restaurant_id'])
            ->where('meal_type', $validated['meal_type'])
            ->first();

        if ($existingMeal) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'This meal type already exists for the selected restaurant. Please edit the existing meal instead.');
        }

        Meal::create($validated);

        return redirect()
            ->route('admin.meals.index')
            ->with('success', 'Meal created successfully.');
    }

    /**
     * Display the specified meal.
     */
    public function show(string $id)
    {
        $meal = Meal::with('restaurant')->findOrFail($id);
        return view('admin.meals.show', compact('meal'));
    }

    /**
     * Show the form for editing the specified meal.
     */
    public function edit(string $id)
    {
        $meal = Meal::findOrFail($id);
        $restaurants = Restaurant::orderBy('restaurant_name')->get();
        $mealTypes = Meal::getMealTypes();

        return view('admin.meals.edit', compact('meal', 'restaurants', 'mealTypes'));
    }

    /**
     * Update the specified meal in storage.
     */
    public function update(Request $request, string $id)
    {
        $meal = Meal::findOrFail($id);

        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'meal_type' => 'required|in:standard_buffet_lunch,standard_buffet_dinner,premium_buffet_lunch,premium_buffet_dinner,cocktail_dinner_without_liquor,cocktail_dinner_with_liquor',
            'menu_description' => 'required|string',
            'price_inr' => 'nullable|numeric|min:0',
            'local_currency' => 'nullable|string|max:10',
            'local_price' => 'nullable|numeric|min:0',
            'supplements' => 'nullable|array',
            'supplements.starter' => 'nullable|array',
            'supplements.starter.available' => 'nullable|boolean',
            'supplements.starter.price' => 'nullable|numeric|min:0',
            'supplements.main_course' => 'nullable|array',
            'supplements.main_course.available' => 'nullable|boolean',
            'supplements.main_course.price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0',
        ]);

        // Check if meal type already exists for this restaurant (excluding current meal)
        $existingMeal = Meal::where('restaurant_id', $validated['restaurant_id'])
            ->where('meal_type', $validated['meal_type'])
            ->where('id', '!=', $id)
            ->first();

        if ($existingMeal) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'This meal type already exists for the selected restaurant.');
        }

        $meal->update($validated);

        return redirect()
            ->route('admin.meals.index')
            ->with('success', 'Meal updated successfully.');
    }

    /**
     * Remove the specified meal from storage.
     */
    public function destroy(string $id)
    {
        $meal = Meal::findOrFail($id);
        $meal->delete();

        return redirect()
            ->route('admin.meals.index')
            ->with('success', 'Meal deleted successfully.');
    }
}
