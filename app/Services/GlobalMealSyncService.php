<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\MealTemplate;
use App\Models\Restaurant;

class GlobalMealSyncService
{
    /**
     * Meal types managed globally (one template for all restaurants; per-restaurant prices).
     *
     * Aligned to standard plans: Lunch, Dinner, Cocktail Dinner, Cocktail Dinner (with hard liquor).
     */
    public static function sharedTemplateMealTypes(): array
    {
        return [
            'standard_buffet_lunch',
            'standard_buffet_dinner',
            'cocktail_dinner_without_liquor',
            'cocktail_dinner_with_liquor',
        ];
    }

    public static function isSharedTemplateType(string $mealType): bool
    {
        return in_array($mealType, self::sharedTemplateMealTypes(), true);
    }

    /**
     * Import column names for starter / main-course supplement prices (per global meal type).
     */
    public static function supplementStarterColumn(string $mealType): string
    {
        return 'meal_supplement_starter_'.$mealType;
    }

    public static function supplementMainCourseColumn(string $mealType): string
    {
        return 'meal_supplement_main_course_'.$mealType;
    }

    /**
     * Create or update a restaurant row for a global meal: copy content from template, apply overrides.
     *
     * @param  array{
     *     price_eur?: ?float,
     *     price?: ?float,
     *     supplements?: ?array<string, array{price?: float}>
     * }  $overrides  Only keys present are applied; others keep existing meal values.
     */
    public function applyTemplateToRestaurantMeal(Restaurant $restaurant, string $mealType, array $overrides): void
    {
        if (! self::isSharedTemplateType($mealType)) {
            return;
        }

        $template = MealTemplate::where('meal_type', $mealType)->first();
        if (! $template) {
            return;
        }

        $existing = Meal::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('meal_type', $mealType)
            ->first();

        // Accept both 'price' (new) and 'price_eur' (legacy callers) in overrides.
        $price = array_key_exists('price', $overrides)
            ? $overrides['price']
            : (array_key_exists('price_eur', $overrides)
                ? $overrides['price_eur']
                : $existing?->price);

        $mergedSupplements = $this->mergeSupplementsForImport(
            $template->supplements,
            $existing?->supplements,
            $overrides['supplements'] ?? null
        );

        Meal::updateOrCreate(
            [
                'restaurant_id' => $restaurant->id,
                'meal_type' => $mealType,
            ],
            [
                'menu_description' => $template->menu_description,
                'supplements' => $mergedSupplements,
                'status' => $template->status,
                'display_order' => $template->display_order,
                'price' => $price,
                'is_shared_template' => true,
            ]
        );
    }

    /**
     * Template supplies availability only (no global supplement prices). Prices come from each restaurant (e.g. import).
     */
    private function mergeSupplementsForImport(?array $templateSup, ?array $existingMealSup, ?array $importPartial): ?array
    {
        $existingMealSup = is_array($existingMealSup) ? $existingMealSup : [];
        $importPartial = is_array($importPartial) ? $importPartial : [];

        $keys = ['starter', 'main_course'];
        $out = [];

        foreach ($keys as $key) {
            if ($templateSup === null) {
                $e = is_array($existingMealSup[$key] ?? null) ? $existingMealSup[$key] : [];
                $avail = (bool) ($e['available'] ?? false);
                $price = null;
                if ($avail && ($e['price'] ?? null) !== null && $e['price'] !== '' && is_numeric($e['price'])) {
                    $price = (float) $e['price'];
                }
                if ($avail && is_array($importPartial[$key] ?? null) && array_key_exists('price', $importPartial[$key])) {
                    $pv = $importPartial[$key]['price'];
                    if ($pv !== null && $pv !== '' && is_numeric($pv)) {
                        $price = (float) $pv;
                    }
                }
                if (! $avail) {
                    $price = null;
                }
                $out[$key] = [
                    'available' => $avail,
                    'price' => $price,
                    'description' => $this->normalizeSupplementDescription($e),
                ];

                continue;
            }

            $t = is_array($templateSup[$key] ?? null) ? $templateSup[$key] : [];
            $e = is_array($existingMealSup[$key] ?? null) ? $existingMealSup[$key] : [];

            $avail = (bool) ($t['available'] ?? false);
            $price = null;

            if ($avail && ($e['price'] ?? null) !== null && $e['price'] !== '' && is_numeric($e['price'])) {
                $price = (float) $e['price'];
            }
            if (! $avail) {
                $price = null;
            }

            if ($avail && is_array($importPartial[$key] ?? null) && array_key_exists('price', $importPartial[$key])) {
                $pv = $importPartial[$key]['price'];
                if ($pv !== null && $pv !== '' && is_numeric($pv)) {
                    $price = (float) $pv;
                }
            }

            $out[$key] = [
                'available' => $avail,
                'price' => $price,
                'description' => $this->normalizeSupplementDescription($t),
            ];
        }

        return $out;
    }

    /**
     * Template sets availability only; restaurant meal keeps its own supplement prices when still available.
     */
    private function mergeSupplementsAfterTemplateEdit(?array $templateSup, ?array $mealSup): ?array
    {
        if ($templateSup === null) {
            return is_array($mealSup) ? $mealSup : null;
        }

        $templateSup = is_array($templateSup) ? $templateSup : [];
        $mealSup = is_array($mealSup) ? $mealSup : [];

        $keys = ['starter', 'main_course'];
        $out = [];

        foreach ($keys as $key) {
            $t = is_array($templateSup[$key] ?? null) ? $templateSup[$key] : [];
            $m = is_array($mealSup[$key] ?? null) ? $mealSup[$key] : [];

            $tAvail = (bool) ($t['available'] ?? false);
            $mPrice = isset($m['price']) && is_numeric($m['price']) ? (float) $m['price'] : null;

            $avail = $tAvail;
            $price = $tAvail ? $mPrice : null;

            $out[$key] = [
                'available' => $avail,
                'price' => $price,
                'description' => $this->normalizeSupplementDescription($t),
            ];
        }

        return $out;
    }

    private function normalizeSupplementDescription(array $supplementBlock): ?string
    {
        if (! array_key_exists('description', $supplementBlock)) {
            return null;
        }
        $raw = $supplementBlock['description'];
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_string($raw)) {
            return null;
        }
        $trimmed = trim($raw);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * After editing a global template, push non-price fields to all linked restaurant meals (preserve per-restaurant supplement prices).
     */
    public function propagateTemplateContentToAllMeals(MealTemplate $template): void
    {
        Meal::query()
            ->where('meal_type', $template->meal_type)
            ->where('is_shared_template', true)
            ->each(function (Meal $meal) use ($template) {
                $mergedSupplements = $this->mergeSupplementsAfterTemplateEdit($template->supplements, $meal->supplements);
                $meal->forceFill([
                    'menu_description' => $template->menu_description,
                    'supplements' => $mergedSupplements,
                    'status' => $template->status,
                    'display_order' => $template->display_order,
                ])->save();
            });
    }
}
