<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralSourceType extends Model
{
    use HasFactory;

    protected $table = 'referral_source_types';

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

    public function referralSources()
    {
        return $this->hasMany(ReferralSource::class, 'referral_source_type_id');
    }
}
