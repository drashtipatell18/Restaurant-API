<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    protected $table = 'families';
    protected $fillable = ['name'];

    public function subfamily()
    {
        return $this->hasMany(Subfamily::class);
    }
}

