<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderMaster extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'order_masters';

    protected $fillable = ['table_id','user_id','box_id','admin_id','order_type','payment_type','status','tip','finish_at','discount','delivery_cost','customer_name','person','reason','transaction_code'];


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




