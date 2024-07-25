<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'payments';
    protected $fillable = ['order_master_id','rut','firstname','lastname','business_name','ltda','tour','address','email','phone','type','amount','return'];
}
