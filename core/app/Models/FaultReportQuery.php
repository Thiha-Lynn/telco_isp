<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaultReportQuery extends Model
{
    use HasFactory;

    protected $table = 'fault_report_query';
    protected $guarded = [];
}
