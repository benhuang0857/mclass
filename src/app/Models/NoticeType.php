<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeType extends Model
{
    use HasFactory;

    protected $table = 'notice_types';

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

    public function notices()
    {
        return $this->hasMany(Notice::class);
    }
}
