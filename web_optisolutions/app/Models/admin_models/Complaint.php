<?php

namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $table = 'complaints';
    protected $primaryKey = 'complaint_id';

    // The table only has created_at (no updated_at column), so let
    // Eloquent manage neither automatically.
    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'log_id',
        'complaint_text',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}