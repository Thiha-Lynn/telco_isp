<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubCompany extends Model
{
    //use SoftDeletes;

   public $table = 'sub_company_list';

   protected $primaryKey = 'sub_com_id';

   
   
}
