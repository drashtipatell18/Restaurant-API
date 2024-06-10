<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item_Menu_Join extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'item__menu__joins';
    protected $fillable = ['menu_id','item_id'];
}
