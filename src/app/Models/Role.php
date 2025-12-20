<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'note',
        'sort',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function members()
    {
        return $this->belongsToMany(Member::class, 'member_role');
    }
    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_role');
    }

}
