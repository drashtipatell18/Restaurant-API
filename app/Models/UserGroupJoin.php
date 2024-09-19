<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserGroupJoin extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'user_group_joins';
    protected $fillable = ['group_id','user_id', 'group_for_chat_id','admin_id'];
}
