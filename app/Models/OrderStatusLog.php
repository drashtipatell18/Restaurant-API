<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStatusLog extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'order_status_logs';
    protected $fillable = ['order_id','status'];
}
