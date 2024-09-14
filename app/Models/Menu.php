<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'menus';
    protected $fillable = ['name','admin_id'];

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item__menu__joins', 'menu_id', 'item_id');
    }
}
