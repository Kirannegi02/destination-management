<?php

namespace App\Models;

use App\Services\GlobalMealSyncService;
use App\Support\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Meal extends Model
{
    use HasFactory;

    /**
     * Meal types with shared menu copy for all restaurants (prices per restaurant).
     */
    public static function sharedTemplateMealTypes(): array
    {
        return GlobalMealSyncService::sharedTemplateMealTypes();
    }

    public static function isSharedTemplateMealType(string $mealType): bool
    {
        return GlobalMealSyncService::isSharedTemplateType($mealType);
    }

    protected $fillable = [
        'restaurant_id',
        'is_shared_template',
        'meal_type',
        'menu_description',
        'price',
        'supplements',
        'status',
        'display_order',
    ];

    protected $casts = [
        'supplements' => 'array',
        'price' => 'decimal:2',
        'display_order' => 'integer',
        'is_shared_template' => 'boolean',
    ];

    /**
     * Get the restaurant that owns the meal.
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get meal type label
     */
    public function getMealTypeLabelAttribute()
    {
        return self::labelForMealTypeKey((string) $this->meal_type);
    }

    /**
     * Human-readable label for a stored meal_type key (built-in or custom slug).
     */
    public static function labelForMealTypeKey(string $mealType): string
    {
        $types = self::getMealTypes();

        return $types[$mealType] ?? Str::title(str_replace('_', ' ', $mealType));
    }

    /**
     * Options for admin combobox (standard labels + any custom types already in DB).
     *
     * @return array<string, string> canonical key => suggestion label
     */
    public static function mealTypePickerOptions(): array
    {
        $options = self::getMealTypes();

        $extra = self::query()
            ->withoutSharedTemplate()
            ->whereNotNull('meal_type')
            ->distinct()
            ->pluck('meal_type');

        foreach ($extra as $type) {
            $type = (string) $type;
            if ($type !== '' && ! array_key_exists($type, $options)) {
                $options[$type] = self::labelForMealTypeKey($type);
            }
        }

        uasort($options, fn ($a, $b) => strcasecmp((string) $a, (string) $b));

        return $options;
    }

    /**
     * Map user input (label, slug, or new text) to stored meal_type key.
     */
    public static function normalizeMealTypeInput(string $input): string
    {
        $input = trim($input);
        if ($input === '') {
            return '';
        }

        foreach (self::getMealTypes() as $key => $label) {
            if ($input === $key) {
                return $key;
            }
            if (strcasecmp($label, $input) === 0) {
                return $key;
            }
        }

        $slug = strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '_', $input));
        $slug = preg_replace('/_+/', '_', $slug);
        $slug = trim((string) $slug, '_');

        return substr($slug, 0, 100);
    }

    /**
     * Formatted price for display (EUR).
     */
    public function getPriceEurFormattedAttribute(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        return Currency::format((float) $this->price);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return $this->status === 'active' ? 'badge-success' : 'badge-danger';
    }

    /**
     * Scope to filter by status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by restaurant
     */
    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    /**
     * Exclude rows that mirror the global menu templates (managed under Global menu).
     */
    public function scopeWithoutSharedTemplate($query)
    {
        return $query->where('is_shared_template', false);
    }

    /**
     * Get all meal types
     */
    public static function getMealTypes()
    {
        return [
            'standard_buffet_lunch' => 'Lunch',
            'standard_buffet_dinner' => 'Dinner',
            'premium_buffet_lunch' => 'Premium Buffet Lunch',
            'premium_buffet_dinner' => 'Premium Buffet Dinner',
            'cocktail_dinner_without_liquor' => 'Cocktail Dinner',
            'cocktail_dinner_with_liquor' => 'Cocktail Dinner (with hard liquor)',
        ];
    }
}
