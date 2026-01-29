<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurposeType extends Model
{
    use HasFactory;

    protected $table = 'purpose_types';

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

    public function purposes()
    {
        return $this->hasMany(Purpose::class, 'purpose_type_id');
    }
}
