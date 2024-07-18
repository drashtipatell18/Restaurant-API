<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderMaster extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'order_masters';
    protected $fillable = ['table_id','user_id','box_id','order_type','payment_type','status','tip','discount','delivery_cost','customer_name','person'];
<<<<<<< HEAD
    protected static function booted()
    {
        static::creating(function ($order) {
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
=======
>>>>>>> aae791964755608b5cb50df51a6ce6579735a497
}
