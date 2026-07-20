<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicInfo extends Model
{
    protected $table = 'clinic_info';
    protected $primaryKey = 'clinic_id';
    public $timestamps = false; 

    protected $fillable = [
        'clinic_name',
        'address',
        'contact_no',
        'email',
        'operating_hours',
        'about_us',
        'mission',
        'vision',
        'core_values',
        'facebook_link',
        'logo_path',
        'website',
    ];

    protected $casts = [
        'core_values' => 'array',
    ];
}