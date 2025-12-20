<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\Commentable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Member extends Authenticatable implements JWTSubject
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

    /**
     * 關聯到通知
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'member_id');
    }

    /**
     * 關聯到通知偏好設定
     */
    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class, 'member_id');
    }

    /**
     * 關聯到追蹤的商品
     */
    public function followedProducts()
    {
        return $this->belongsToMany(Product::class, 'follower_club_course_info', 'member_id', 'product_id');
    }

    /**
     * 關聯到諮商預約（作為學生）
     */
    public function counselingAppointments()
    {
        return $this->hasMany(CounselingAppointment::class, 'student_id');
    }

    /**
     * 關聯到諮商預約（作為諮商師）
     */
    public function counselorAppointments()
    {
        return $this->hasMany(CounselingAppointment::class, 'counselor_id');
    }

    /**
     * 關聯到建立的輪播圖
     */
    public function createdSlideshows()
    {
        return $this->hasMany(Slideshow::class, 'created_by');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
