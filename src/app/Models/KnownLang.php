<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnownLang extends Model
{
    use HasFactory;

    protected $table = 'known_langs';

    protected $fillable = [
        'lang_type_id',
        'member_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * 關聯到語言類型
     */
    public function langType()
    {
        return $this->belongsTo(LangType::class, 'lang_type_id');
    }

    /**
     * 關聯到會員
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
