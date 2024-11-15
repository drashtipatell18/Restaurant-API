<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'families';
    protected $fillable = ['name','admin_id'];

    public function subfamily()
    {
        return $this->hasMany(Subfamily::class);
    }
     public function items()
    {
        return $this->hasMany(Item::class);
    }
}

