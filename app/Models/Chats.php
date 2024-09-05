<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chats extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'chats';
    protected $fillable = ['sender_id','receiver_id', 'group_id', 'message', 'read_by'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
    public function group()
    {
        return $this->belongsTo(GroupForChat::class, 'group_id');
    }
}
