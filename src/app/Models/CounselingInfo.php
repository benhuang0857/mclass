<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Commentable;

class CounselingInfo extends Model
{
    use HasFactory, Commentable;

    protected $table = 'counseling_infos';

    protected $fillable = [
        'product_id',
        'name',
        'code',
        'description',
        'details',
        'feature_img',
        'counseling_mode',
        'session_duration',
        'total_sessions',
        'allow_reschedule',
        'status',
    ];

    protected $casts = [
        'allow_reschedule' => 'boolean',
        'session_duration' => 'integer',
        'total_sessions' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * 获取完整的 feature_img URL
     */
    public function getFeatureImgAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // 如果已经是完整 URL，直接返回
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // 拼接完整 URL
        return url($value);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function counselors()
    {
        return $this->belongsToMany(Member::class, 'counseling_info_counselors', 'counseling_info_id', 'counselor_id')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    public function appointments()
    {
        return $this->hasMany(CounselingAppointment::class, 'counseling_info_id');
    }
}
