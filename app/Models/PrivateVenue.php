<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PrivateVenue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'venue_type',
        'brand_chain',
        'description',
        'highlights',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'latitude',
        'longitude',
        'phone',
        'email',
        'contact_name',
        'website',
        'images',
        'video',
        'star_rating',
        'total_meeting_space_sqm',
        'largest_room_capacity',
        'number_of_meeting_rooms',
        'sleeping_rooms',
        'min_event_size',
        'max_event_size',
        'currency',
        'starting_daily_rate',
        'pricing_notes',
        'amenities',
        'event_types',
        'is_featured',
        'display_order',
        'status',
        'internal_notes',
    ];

    protected $casts = [
        'images' => 'array',
        'amenities' => 'array',
        'event_types' => 'array',
        'is_featured' => 'boolean',
        'starting_daily_rate' => 'decimal:2',
        'total_meeting_space_sqm' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PrivateVenue $venue) {
            if (empty($venue->slug)) {
                $venue->slug = static::uniqueSlug($venue->name);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'venue';
        $slug = $base;
        $n = 1;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    /** Cvent-style venue categories */
    public static function venueTypes(): array
    {
        return [
            'hotel' => 'Hotel',
            'conference_center' => 'Conference center',
            'convention_center' => 'Convention center',
            'restaurant' => 'Restaurant / private dining',
            'unique_space' => 'Unique space',
            'museum' => 'Museum / gallery',
            'outdoor' => 'Outdoor venue',
            'other' => 'Other',
        ];
    }

    /** Event types planners search for (Cvent-style) */
    public static function eventTypesList(): array
    {
        return [
            'meeting' => 'Meeting',
            'conference' => 'Conference',
            'corporate_event' => 'Corporate event',
            'incentive' => 'Incentive / retreat',
            'wedding' => 'Wedding',
            'social' => 'Social event',
            'exhibition' => 'Exhibition / trade show',
            'training' => 'Training / workshop',
            'gala' => 'Gala dinner',
        ];
    }

    /** Venue-level amenities */
    public static function venueAmenitiesList(): array
    {
        return [
            'wifi' => 'Wi-Fi',
            'parking' => 'Parking',
            'valet_parking' => 'Valet parking',
            'av_equipment' => 'AV equipment',
            'projector' => 'Projector & screen',
            'sound_system' => 'Sound system',
            'catering' => 'In-house catering',
            'bar' => 'Bar service',
            'ac' => 'Air conditioning',
            'natural_light' => 'Natural daylight',
            'business_center' => 'Business center',
            'breakout_rooms' => 'Breakout rooms',
            'exhibition_space' => 'Exhibition space',
            'accessibility' => 'Wheelchair accessible',
            'accommodation' => 'Guest rooms on site',
        ];
    }

    /** Room setup styles with max capacity (Cvent meeting room capacities) */
    public static function setupTypes(): array
    {
        return [
            'theater' => 'Theater',
            'classroom' => 'Classroom',
            'banquet' => 'Banquet rounds',
            'u_shape' => 'U-shape',
            'conference' => 'Conference',
            'reception' => 'Reception / standing',
            'hollow_square' => 'Hollow square',
            'cabaret' => 'Cabaret',
        ];
    }

    /** Per-room equipment / features */
    public static function spaceAmenitiesList(): array
    {
        return [
            'projector' => 'Projector',
            'screen' => 'Projection screen',
            'whiteboard' => 'Whiteboard',
            'flipchart' => 'Flip chart',
            'microphone' => 'Microphone',
            'video_conferencing' => 'Video conferencing',
            'stage' => 'Stage',
            'dance_floor' => 'Dance floor',
            'dim_lights' => 'Dimming lights',
            'built_in_av' => 'Built-in AV',
        ];
    }

    public function spaces(): HasMany
    {
        return $this->hasMany(PrivateVenueSpace::class)->orderByDesc('sort_order')->orderBy('id');
    }

    public function activeSpaces(): HasMany
    {
        return $this->spaces()->where('status', 'active');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(PrivateVenueBooking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getVenueTypeLabelAttribute(): string
    {
        return static::venueTypes()[$this->venue_type] ?? ucfirst(str_replace('_', ' ', $this->venue_type));
    }

    public function getLocationLabelAttribute(): string
    {
        return collect([$this->city, $this->state, $this->country])->filter()->implode(', ');
    }

    public function getStartingRateFormattedAttribute(): ?string
    {
        if ($this->starting_daily_rate === null) {
            return null;
        }

        $sym = match (strtoupper($this->currency ?? 'EUR')) {
            'CHF' => 'CHF ',
            'USD' => '$',
            'GBP' => '£',
            default => '€',
        };

        return $sym.number_format((float) $this->starting_daily_rate, 2);
    }
}
