<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $table = 'schools';

    protected $fillable = [
        'school_type_id',
        'member_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * 關聯到學校類型
     */
    public function schoolType()
    {
        return $this->belongsTo(SchoolType::class, 'school_type_id');
    }

    /**
     * 關聯到會員
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
