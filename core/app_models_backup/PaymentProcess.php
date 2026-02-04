<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentProcess extends Model
{
    public $table = 'payment_processes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'bind_id',
        'description',
        'status'
    ];
}
