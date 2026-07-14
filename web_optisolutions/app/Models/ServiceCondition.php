<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCondition extends Model
{
    protected $table = 'service_conditions';
    public $timestamps = false;
    protected $fillable = ['service_id', 'condition_name'];
}