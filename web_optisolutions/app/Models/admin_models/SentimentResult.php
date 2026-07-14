<?php

namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;

class SentimentResult extends Model
{
    protected $table = 'sentiment_results';
    protected $primaryKey = 'sentiment_id';
    public $timestamps = false; // uses analyzed_at instead

    protected $fillable = [
        'feedback_id',
        'sentiment_label',
        'confidence_score',
        'analyzed_at',
    ];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class, 'feedback_id', 'feedback_id');
    }
}