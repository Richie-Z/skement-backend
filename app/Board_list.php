<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Board_list extends Model
{
    protected $table = 'board_lists';
    protected $hidden = ['created_at', 'updated_at', 'pivot'];
    protected $with = ['card'];
    protected $fillable = ['board_id', 'order', 'name'];
    public function card()
    {
        return $this->hasMany('App\Card', 'list_id');
    }
}
