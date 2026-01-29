<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $table = 'levels';

    protected $fillable = [
        'level_type_id',
        'member_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * 關聯到等級類型
     */
    public function levelType()
    {
        return $this->belongsTo(LevelType::class, 'level_type_id');
    }

    /**
     * 關聯到會員
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
