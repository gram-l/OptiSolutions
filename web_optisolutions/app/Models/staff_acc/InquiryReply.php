<?php
// app/Models/InquiryReply.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryReply extends Model
{
    protected $fillable = ['inquiry_id', 'sender', 'message', 'is_staff'];

    protected $casts = ['is_staff' => 'boolean'];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function toApiArray(): array
    {
        return [
            'sender' => $this->sender,
            'message' => $this->message,
            'time' => $this->created_at->format('g:i A'),
            'isStaff' => $this->is_staff,
        ];
    }
}