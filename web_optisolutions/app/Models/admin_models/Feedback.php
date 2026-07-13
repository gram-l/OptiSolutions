<?php

namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';
    protected $primaryKey = 'feedback_id';
    public $timestamps = false; // uses submitted_at instead of created_at/updated_at

    protected $fillable = [
        'log_id',
        'patient_id',
        'feedback_text',
        'star_rating',
        'submitted_at',
    ];

    public function sentimentResult()
    {
        return $this->hasOne(SentimentResult::class, 'feedback_id', 'feedback_id');
    }
}