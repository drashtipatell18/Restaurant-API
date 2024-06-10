<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet_log extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'wallet_logs';
    protected $fillable = ['transcation_id','wallet_id','credit_amount','transcation_type'];

    public function wallet()
    {
    return $this->belongsTo(Wallet::class);
    }
}
