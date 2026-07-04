<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicInfo extends Model
{
    protected $table = 'clinic_info';
    protected $primaryKey = 'clinic_id';
    public $timestamps = false;
}