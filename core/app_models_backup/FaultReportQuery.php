<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FaultReportQuery extends Model
{
	protected $primaryKey = 'id';
	protected $table = 'fault_report_query';
    protected $fillable = [ 'user_id', 'sub_com_id', 'fault_number', 'report_date', 'fault_address', 'fault_details','fault_status', 'created_at', 'updated_at'];
}
