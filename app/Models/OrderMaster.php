<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderMaster extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'order_masters';
    protected $fillable = ['table_id','user_id','box_id','order_type','payment_type','status','tip','discount','delivery_cost', 'notes'];
}
