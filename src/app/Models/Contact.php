<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['member_id', 'city', 'region', 'address', 'mobile', 'mobile_valid'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
