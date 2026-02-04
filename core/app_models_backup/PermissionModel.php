<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PermissionModel extends Model
{
   public $table = 'permission_role';

   protected $primaryKey = 'p_role_id';
   protected $fillable = [
        'role_id',
        'permission_id'
     
    ];
    // public function admin()
    // {
    //     return $this->hasOne(Admin::class, 'role_id');
        
    // }

   
}
