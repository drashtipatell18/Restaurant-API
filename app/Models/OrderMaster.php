<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderMaster extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'order_masters';
<<<<<<< Updated upstream
    protected $fillable = ['table_id','user_id','box_id','order_type','payment_type','status','tip','discount','delivery_cost','customer_name','person','reason'];
=======
    protected $fillable = ['table_id','user_id','box_id','order_type','payment_type','status','tip','discount','delivery_cost','customer_name','person'];
>>>>>>> Stashed changes

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
<<<<<<< Updated upstream
}
=======

}
>>>>>>> Stashed changes
