<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNot extends Model
{
    use HasFactory;

protected $table = "credit_notes";
    protected $fillable = [
        'order_id',
        'payment_id',
        'name',
        'email',
        'code',
        'destination',
        'status',
        'credit_method',
        'admin_id'
    ];

    public function orderMaster()
    {
        return $this->belongsTo(OrderMaster::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class, 'credit_note_id');
    }
}

