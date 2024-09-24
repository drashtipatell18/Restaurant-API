<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Table extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'restauranttables';
    protected $fillable = ['user_id','sector_id','admin_id','name','status'];

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }
}
