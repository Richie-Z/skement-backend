<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $table = 'cards';
    protected $hidden = ['created_at', 'updated_at', 'pivot'];
    protected $fillable = ['list_id', 'order', 'task'];
    public function list()
    {
        return $this->belongsTo('App/Board_list');
    }
}
