<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Commentable;

class Product extends Model
{
    use HasFactory, Commentable;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'code',
        'feature_img',
        'regular_price',
        'discount_price',
        'limit_enrollment',
        'max_enrollment',
        'stock',
        'is_series',
        'elective',
        'is_visible_to_specific_students',
        'status',
    ];

    protected $casts = [
        'limit_enrollment' => 'boolean',
        'is_series' => 'boolean',
        'elective' => 'boolean',
        'is_visible_to_specific_students' => 'boolean',
        'regular_price' => 'float',
        'discount_price' => 'float',
        'max_enrollment' => 'integer',
        'stock' => 'integer',
        'status' => 'string',
    ];

    public function clubCourseInfo()
    {
        return $this->hasOne(ClubCourseInfo::class, 'product_id');
    }

    /**
     * 翻轉課程資訊 (一對一關聯)
     */
    public function flipCourseInfo()
    {
        return $this->hasOne(FlipCourseInfo::class, 'product_id');
    }

    /**
     * 追蹤者 (多對多關聯)
     */
    public function followers()
    {
        return $this->belongsToMany(Member::class, 'follower_club_course_info', 'product_id', 'member_id');
    }

    /**
     * 可見學生 (多對多關聯)
     */
    public function visibleStudents()
    {
        return $this->belongsToMany(Member::class, 'visibler_club_course_info', 'product_id', 'member_id');
    }
}
