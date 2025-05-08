<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Background extends Model
{
    use HasFactory;

    protected $table = 'backgrounds';

    protected $fillable = [
        'member_id', 'goals', 'purposes', 'highest_education', 
        'school', 'department', 'certificates'
    ];

    protected $casts = [
        'lang_types' => 'array',
        'goals' => 'array',
        'purposes' => 'array',
        'certificates' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function languages()
    {
        return $this->belongsToMany(LangType::class, 'lang_type_background');
    }

    public function levels()
    {
        return $this->belongsToMany(LevelType::class, 'level_type_background');
    }
}
