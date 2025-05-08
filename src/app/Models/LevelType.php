<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelType extends Model
{
    use HasFactory;

    protected $table = 'level_types';

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
}
