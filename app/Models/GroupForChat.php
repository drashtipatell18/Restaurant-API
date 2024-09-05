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

    public function chats()
    {
        return $this->hasMany(Chats::class, 'group_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_group_joins', 'group_id', 'user_id');
    }
}
