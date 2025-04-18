<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Background extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 'lang_types', 'goals', 'purposes', 'level',
        'highest_education', 'school', 'department', 'certificates'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
