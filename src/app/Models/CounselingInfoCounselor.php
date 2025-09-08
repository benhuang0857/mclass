<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounselingInfoCounselor extends Model
{
    use HasFactory;

    protected $table = 'counseling_info_counselors';

    protected $fillable = [
        'counseling_info_id',
        'counselor_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function counselingInfo()
    {
        return $this->belongsTo(CounselingInfo::class, 'counseling_info_id');
    }

    public function counselor()
    {
        return $this->belongsTo(Member::class, 'counselor_id');
    }
}
