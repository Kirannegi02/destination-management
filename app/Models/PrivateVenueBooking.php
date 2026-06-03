<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateVenueBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'private_venue_id',
        'private_venue_space_id',
        'event_name',
        'event_type',
        'event_date_start',
        'event_date_end',
        'start_time',
        'end_time',
        'guests',
        'setup_style',
        'estimated_total',
        'currency',
        'status',
        'special_requests',
        'contact_name',
        'contact_phone',
        'contact_email',
        'metadata',
    ];

    protected $casts = [
        'event_date_start' => 'date',
        'event_date_end' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'guests' => 'integer',
        'estimated_total' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(PrivateVenue::class, 'private_venue_id');
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(PrivateVenueSpace::class, 'private_venue_space_id');
    }
}
