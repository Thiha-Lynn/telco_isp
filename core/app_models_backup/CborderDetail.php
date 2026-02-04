<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CborderDetail extends Model
{
    protected $primaryKey = 'id';
    protected $table      = 'cborder_details';
    protected $fillable   = ['orders', 'notify_data'];
 
}
