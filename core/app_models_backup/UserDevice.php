<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $primaryKey = 'id';
    protected $table      = 'user_devices';
    protected $fillable   = ['user_id','device_id','fcm_token','language_id'];
 
}
