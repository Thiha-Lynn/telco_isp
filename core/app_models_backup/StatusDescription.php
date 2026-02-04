<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StatusDescription extends Model
{
    protected $table = 'status_description';
	protected $primaryKey = 'status_id';
    protected $fillable = [
        'status_name', 'is_active', 'created_at', 'status_class','updated_at'
    ];
}
