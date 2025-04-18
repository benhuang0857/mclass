<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = ['member_id', 'lastname', 'firstname', 'gender', 'birthday', 'job'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
