<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    //use SoftDeletes;

   public $table = 'role';

   protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'role_password',
        'created_at',
        'updated_at',
        'created_by',
    ];

    // public function users()
    // {
    //     return $this->belongsToMany(User::class);
    // }

    // public function permissions()
    // {
    //     return $this->belongsToMany(Permission::class);
    // }
   
}
