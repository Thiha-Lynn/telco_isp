<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class UserQuery extends Model
{
    protected $primaryKey = 'apply_id';
    protected $table = 'user_query';
    protected $fillable = [
        'user_id', 'user_number', 'contact_name','address', 'reporting_time','apply_date_start', 'for_filter','apply_date_end', 'fault_number','fault_status','sub_company', 'query_status', 'created_at', 'updated_at'
    ];
}
