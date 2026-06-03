<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateVenueSpace extends Model
{
    use HasFactory;

    protected $fillable = [
        'private_venue_id',
        'name',
        'description',
        'total_space_sqm',
        'length_m',
        'width_m',
        'ceiling_height_m',
        'setup_capacities',
        'amenities',
        'is_outdoor',
        'is_private',
        'is_semi_private',
        'wheelchair_accessible',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'setup_capacities' => 'array',
        'amenities' => 'array',
        'is_outdoor' => 'boolean',
        'is_private' => 'boolean',
        'is_semi_private' => 'boolean',
        'wheelchair_accessible' => 'boolean',
        'total_space_sqm' => 'decimal:2',
        'length_m' => 'decimal:2',
        'width_m' => 'decimal:2',
        'ceiling_height_m' => 'decimal:2',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(PrivateVenue::class, 'private_venue_id');
    }

    public function getMaxCapacityAttribute(): ?int
    {
        $caps = $this->setup_capacities;
        if (! is_array($caps) || $caps === []) {
            return null;
        }

        $values = array_filter(array_map('intval', $caps));

        return $values !== [] ? max($values) : null;
    }

    public function getDimensionsLabelAttribute(): ?string
    {
        $parts = [];
        if ($this->length_m && $this->width_m) {
            $parts[] = $this->length_m.'m × '.$this->width_m.'m';
        }
        if ($this->ceiling_height_m) {
            $parts[] = 'H '.$this->ceiling_height_m.'m';
        }
        if ($this->total_space_sqm) {
            $parts[] = $this->total_space_sqm.' m²';
        }

        return $parts !== [] ? implode(' · ', $parts) : null;
    }
}
