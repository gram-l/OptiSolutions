<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDoctor extends Model
{
    protected $table = 'service_doctors';
    public $timestamps = false;
    protected $fillable = ['service_id', 'doctor_name'];
}