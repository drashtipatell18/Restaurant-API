<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupForChat extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'group_for_chats';
    protected $fillable = ['name','photo'];
}
