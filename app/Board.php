<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    protected $table = 'boards';
    public $timestamps = false;
    protected $hidden = ['created_at', 'updated_at', 'pivot'];
    protected $fillable = ['creator_id', 'name'];

    public function user()
    {
        return $this->belongsToMany('App\User', 'board_members', 'board_id', 'user_id');
    }
    public function list()
    {
        return $this->hasMany('App\Board_list');
    }
}
