<?php
// app/Models/InquiryReply.php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class InquiryReply extends Model
{
    protected $primaryKey = 'reply_id';

    protected $fillable = ['inquiry_id', 'user_id', 'sender', 'message'];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    // "Virtual" column — kinukwenta na lang mula sa sender, hindi na
    // nangangailangan ng totoong is_staff column sa database
    public function getIsStaffAttribute()
    {
        return $this->sender !== 'Patient';
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