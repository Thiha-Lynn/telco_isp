<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bcategory extends Model
{
    protected $guarded = [];
    public function blogs()
    {
        return $this->hasMany('App\Blog');
    }
}
