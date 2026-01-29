<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalType extends Model
{
    use HasFactory;

    protected $table = 'goal_types';

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

    public function goals()
    {
        return $this->hasMany(Goal::class, 'goal_type_id');
    }
}
