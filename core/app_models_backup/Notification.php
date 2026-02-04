<?php

namespace App;
use Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
class Notification extends Model
{
    protected $table      = 'notification';
    protected $primaryKey = 'notification_id';
    protected $fillable   = ['account_id', 'install_user_id','publish_info', 'status', 'created_at', 'updated_at'];
       
    
   
}
