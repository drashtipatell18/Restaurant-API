<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item_Production_Join extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'item__production__joins';
    protected $fillable = ['production_id','item_id','admin_id'];
}
