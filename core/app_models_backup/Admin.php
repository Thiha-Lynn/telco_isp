<?php

namespace App;
use Auth;
use App\Models\PermissionModel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'username', 'email', 'email_verified', 'role_id', 'image', 'password', 'uniqid', 'phone', 'sub_company', 'user_status'
       
    ];
    public function get_role() {
          $permission = PermissionModel::where('role_id',Auth::guard('admin')->user()->role_id)->get(['permission_id']);
          return $permission;
    }
    // public function role() {
    //     return $this->hasMany('App\Models\PermissionModel',"role_id");
    // }
   

}
