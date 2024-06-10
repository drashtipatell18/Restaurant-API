<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Boxs extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'boxs';
    protected $fillable = ['user_id','name'];
}
