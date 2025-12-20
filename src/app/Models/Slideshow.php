<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Slideshow extends Model
{
    use HasFactory;

    protected $table = 'slideshows';

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'link_url',
        'slideshow_type_id',
        'start_date',
        'end_date',
        'device',
        'display_order',
        'status',
        'created_by',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'display_order' => 'integer',
        'slideshow_type_id' => 'integer',
        'created_by' => 'integer',
    ];

    // Relationships

    public function slideshowType()
    {
        return $this->belongsTo(SlideshowType::class);
    }

    public function creator()
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    // Query Scopes

    /**
     * Scope to get only published slideshows
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get active slideshows (published and within date range)
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();
        return $query->where('status', 'published')
                     ->where(function($q) use ($now) {
                         $q->whereNull('start_date')
                           ->orWhere('start_date', '<=', $now);
                     })
                     ->where(function($q) use ($now) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', $now);
                     });
    }

    /**
     * Scope to filter by device
     */
    public function scopeForDevice($query, $device)
    {
        return $query->whereIn('device', ['all', $device]);
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')
                     ->orderBy('created_at', 'desc');
    }

    // Helper Methods

    /**
     * Check if slideshow is currently active
     */
    public function isActive()
    {
        if ($this->status !== 'published') {
            return false;
        }

        $now = Carbon::now();

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if slideshow can be displayed on a specific device
     */
    public function canDisplayOnDevice($device)
    {
        return $this->device === 'all' || $this->device === $device;
    }
}
