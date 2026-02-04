<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function packageorders(): HasMany
    {
        return $this->hasMany(Packageorder::class, 'package_id');
    }

    public function billpaids(): HasMany
    {
        return $this->hasMany(Billpaid::class, 'package_id');
    }
}
