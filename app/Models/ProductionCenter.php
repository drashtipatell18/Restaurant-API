<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionCenter extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'production_centers';
    protected $fillable = ['name','printer_code'];
}
