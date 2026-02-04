<?php

namespace App;
use Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
class MbtBindUser extends Model
{
    protected $table      = 'mbt_bind_user';
    protected $primaryKey = 'er_id';
    //protected $fillable   = [];
  
}
