<?php

use App\Models\Meal;
use App\Models\MealTemplate;
use App\Models\Restaurant;
use App\Services\GlobalMealSyncService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Global plans: Lunch, Dinner, Cocktail Dinner, Cocktail Dinner (with hard liquor).
     * Replaces premium_buffet_lunch as a shared template. Syncs menu copy to all restaurants.
     */
    public function up(): void
    {
        $lunchDinnerMenu = '2+2 Maincourse, Dal, Rice, Curd, Roti, Salad, Condiments, 1 Sweet Dish';
        $cocktailBase = '2+2 Starters, 2+2 Maincourse, Dal, Rice, Curd, Roti, Salad, Condiments, 2 Sweet Dish + Free flow Beverages and Beer for 2 Hour';
        $cocktailLiquor = $cocktailBase.' + Hard Liquor';

        $cocktailSupplements = [
            'starter' => [
                'available' => true,
                'price' => null,
                'description' => '2+2 Starters',
            ],
            'main_course' => [
                'available' => true,
                'price' => null,
                'description' => '2+2 Maincourse',
            ],
        ];

        $rows = [
            [
                'meal_type' => 'standard_buffet_lunch',
                'menu_description' => $lunchDinnerMenu,
                'supplements' => null,
                'status' => 'active',
                'display_order' => 0,
            ],
            [
                'meal_type' => 'standard_buffet_dinner',
                'menu_description' => $lunchDinnerMenu,
                'supplements' => null,
                'status' => 'active',
                'display_order' => 1,
            ],
            [
                'meal_type' => 'cocktail_dinner_without_liquor',
                'menu_description' => $cocktailBase,
                'supplements' => $cocktailSupplements,
                'status' => 'active',
                'display_order' => 2,
            ],
            [
                'meal_type' => 'cocktail_dinner_with_liquor',
                'menu_description' => $cocktailLiquor,
                'supplements' => $cocktailSupplements,
                'status' => 'active',
                'display_order' => 3,
            ],
        ];

        foreach ($rows as $row) {
            MealTemplate::updateOrCreate(
                ['meal_type' => $row['meal_type']],
                [
                    'menu_description' => $row['menu_description'],
                    'supplements' => $row['supplements'],
                    'status' => $row['status'],
                    'display_order' => $row['display_order'],
                ]
            );
        }

        MealTemplate::where('meal_type', 'premium_buffet_lunch')->delete();

        Meal::query()
            ->where('meal_type', 'premium_buffet_lunch')
            ->where('is_shared_template', true)
            ->update(['is_shared_template' => false]);

        $sync = app(GlobalMealSyncService::class);
        foreach (Restaurant::query()->cursor() as $restaurant) {
            foreach (GlobalMealSyncService::sharedTemplateMealTypes() as $mealType) {
                $sync->applyTemplateToRestaurantMeal($restaurant, $mealType, []);
            }
        }
    }

    public function down(): void
    {
        MealTemplate::whereIn('meal_type', [
            'cocktail_dinner_without_liquor',
            'cocktail_dinner_with_liquor',
        ])->delete();

        MealTemplate::updateOrCreate(
            ['meal_type' => 'premium_buffet_lunch'],
            [
                'menu_description' => 'Premium buffet lunch — update description under Admin → Global menu.',
                'supplements' => null,
                'status' => 'active',
                'display_order' => 2,
            ]
        );

        Meal::query()
            ->whereIn('meal_type', ['cocktail_dinner_without_liquor', 'cocktail_dinner_with_liquor'])
            ->where('is_shared_template', true)
            ->update(['is_shared_template' => false]);

        // Restore `sharedTemplateMealTypes()` in code to the previous three types if you fully roll back.
    }
};
