<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Wallet extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'wallets';
    protected $fillable = ['credit','user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
