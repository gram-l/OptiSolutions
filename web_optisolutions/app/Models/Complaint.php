<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $table = 'complaints';
    protected $primaryKey = 'complaint_id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = ['patient_id', 'log_id', 'complaint_text', 'status', 'category'];
}