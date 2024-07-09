<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'items';
    protected $fillable = ['name','code','production_center_id','sub_family_id','family_id','cost_price','sale_price','description','image'];

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'item__menu__joins', 'item_id', 'menu_id');
    }
}
