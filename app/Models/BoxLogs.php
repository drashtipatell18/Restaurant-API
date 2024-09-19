<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoxLogs extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'box_logs';
    protected $fillable = ['box_id','open_amount','open_time','open_by','close_by','close_time','close_amount','cash_amount', 'collected_amount', 'payment_id', 'order_master_id','admin_id'];}
