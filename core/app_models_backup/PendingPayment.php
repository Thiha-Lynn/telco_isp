<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PendingPayment extends Model
{
    protected $primaryKey = 'id';
    protected $table      = 'pending_payments';
    protected $fillable   = ['user_name', 'order_no','payment_id','amount','number','begin_date','expire_time','phone','discount','commercial_tax','generateRefOrder'];
 
}
