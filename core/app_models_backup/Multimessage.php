<?php

namespace App;
use Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
class multimessage extends Model
{
    protected $table      = 'mbt_bind_user';
    protected $primaryKey = 'reg_phone';
    //protected $fillable   = [];
  
}
