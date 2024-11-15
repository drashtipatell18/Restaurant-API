<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;
    protected $table ="returns_item";

    protected $fillable = [
        'credit_note_id',
        'item_id',
        'name',
        'quantity',
        'cost',
        'amount',
        'notes',
    ];

    public function creditNote()
    {
        return $this->belongsTo(CreditNot::class, 'credit_note_id');
    }

    // Define the relationship with the Item model
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}


