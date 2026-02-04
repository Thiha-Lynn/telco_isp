<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    public function orderitems() {
        return $this->hasMany('App\OrderItem');
    }
}
