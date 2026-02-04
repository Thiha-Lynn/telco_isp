<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WaveCallback extends Model
{
    protected $primaryKey = 'id';
    protected $table      = 'wave_callbacks';
    protected $fillable   = ['additionalField1', 'additionalField2', 'additionalField3', 'additionalField4', 'additionalField5', 'amount', 'backendResultUrl', 'currency', 'frontendResultUrl', 'hashValue', 'initiatorMsisdn', 'merchantId', 'merchantReferenceId', 'orderId', 'paymentDescription', 'paymentRequestId', 'requestTime', 'status', 'timeToLiveSeconds', 'transactionId'];
 
}
