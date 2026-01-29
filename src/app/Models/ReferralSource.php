<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralSource extends Model
{
    use HasFactory;

    protected $table = 'referral_sources';

    protected $fillable = [
        'referral_source_type_id',
        'member_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * 關聯到來源類型
     */
    public function referralSourceType()
    {
        return $this->belongsTo(ReferralSourceType::class, 'referral_source_type_id');
    }

    /**
     * 關聯到會員
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
