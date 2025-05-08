<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $fillable = ['nickname', 'account', 'email', 'email_valid', 'password', 'status'];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    public function background()
    {
        return $this->hasOne(Background::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'member_role');
    }
}
