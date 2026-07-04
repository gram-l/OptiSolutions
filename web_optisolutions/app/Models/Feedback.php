<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';
    protected $primaryKey = 'feedback_id';

    const CREATED_AT = 'submitted_at';
    const UPDATED_AT = null;

    protected $fillable = ['log_id', 'patient_id', 'feedback_text', 'star_rating'];
}