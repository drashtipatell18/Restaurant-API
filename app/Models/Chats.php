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
}
