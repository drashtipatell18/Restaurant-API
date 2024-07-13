<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDetails extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'order_details';
    protected $fillable = ['order_master_id','item_id','amount','quantity','notes', 'cost'];
}
