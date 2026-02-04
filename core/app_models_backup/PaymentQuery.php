<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentQuery extends Model
{
    protected $primaryKey = 'id';
    protected $table      = 'payment_query';
    protected $fillable   = ['sub_com_id', 'product_id','user_id','begin_date','expire_date','order_id','payment_user_name','pack_expiery_date', 'trans_date', 'transaction_id', 'total_amt', 'invoice_no','admin_status','for_filter','payment_method', 'package_id', 'status','phone'];
 
}
