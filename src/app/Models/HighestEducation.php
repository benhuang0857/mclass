<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HighestEducation extends Model
{
    use HasFactory;

    protected $table = 'highest_education';

    protected $fillable = [
        'education_type_id',
        'member_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * 關聯到學歷類型
     */
    public function educationType()
    {
        return $this->belongsTo(EducationType::class, 'education_type_id');
    }

    /**
     * 關聯到會員
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
