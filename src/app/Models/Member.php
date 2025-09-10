<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Commentable;

class Member extends Model
{
    use HasFactory, Commentable;

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

    /**
     * 關聯到出席記錄
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'member_id');
    }

    /**
     * 關聯到訂單
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'member_id');
    }
}
