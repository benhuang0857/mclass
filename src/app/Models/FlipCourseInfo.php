<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlipCourseInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'code',
        'description',
        'details',
        'feature_img',
        'teaching_mode',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'teaching_mode' => 'string',
        'status' => 'string',
    ];

    /**
     * 對應的商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 最後更新者
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 語言類型 (多對多)
     */
    public function langTypes(): BelongsToMany
    {
        return $this->belongsToMany(LangType::class, 'lang_type_flip_course_info');
    }

    /**
     * 所有案例
     */
    public function cases(): HasMany
    {
        return $this->hasMany(FlipCourseCase::class);
    }

    /**
     * 進行中的案例
     */
    public function activeCases(): HasMany
    {
        return $this->hasMany(FlipCourseCase::class)
            ->whereNotIn('workflow_stage', ['completed', 'cancelled']);
    }
}
