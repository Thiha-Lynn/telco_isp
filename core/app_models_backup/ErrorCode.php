<?php

namespace App;
use Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
class ErrorCode extends Model
{
    protected $table      = 'error_code';
    protected $primaryKey = 'error_id';
    //protected $fillable   = [];
       
    
   
}
