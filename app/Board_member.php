<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Board_member extends Model
{
    protected $table = 'board_members';
    protected $fillable = ['board_id', 'user_id'];
}
