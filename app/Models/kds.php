<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class kds extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'kds';
    protected $fillable = ['table_id','user_id','box_id','order_id','order_type','finished_at','payment_type','status','tip','discount','delivery_cost','customer_name','person','reason','admin_id'];

}
