<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Commentable;

class Notice extends Model
{
    use HasFactory, Commentable;

    protected $table = 'notices';

    protected $fillable = [
        'title',
        'feature_img',
        'notice_type_id',
        'body',
        'status',
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

    public function noticeType()
    {
        return $this->belongsTo(NoticeType::class);
    }
}
