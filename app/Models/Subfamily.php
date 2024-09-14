<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subfamily extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'subfamilies';
    protected $fillable = ['family_id','name','admin_id'];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }
}
