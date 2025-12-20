<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlideshowType extends Model
{
    use HasFactory;

    protected $table = 'slideshow_types';

    protected $fillable = [
        'name',
        'slug',
        'note',
        'sort',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort' => 'integer',
    ];

    public function slideshows()
    {
        return $this->hasMany(Slideshow::class);
    }
}
