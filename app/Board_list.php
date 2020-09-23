<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Board_list extends Model
{
    protected $table = 'board_lists';
    protected $fillable = ['board_id', 'order', 'name'];
}
