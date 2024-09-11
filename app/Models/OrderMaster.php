<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderMaster extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'order_masters';

    protected $fillable = ['table_id','user_id','box_id','order_type','finished_at','payment_type','status','tip','discount','delivery_cost','customer_name','person','reason'];


    protected static function booted()
    {
        static::created(function ($order) {
            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => $order->status,
            ]);
        });

        static::updating(function ($order) {
            if ($order->isDirty('status')) {
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                ]);
            }
        });
    }
}




