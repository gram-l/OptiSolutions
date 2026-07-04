<?php
// app/Models/Inquiry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $fillable = ['inquiry_code', 'patient_code', 'department', 'is_new'];

    protected $casts = ['is_new' => 'boolean'];

    public function messages()
    {
        return $this->hasMany(InquiryReply::class)->orderBy('created_at');
    }

    public function toApiArray(): array
    {
        $lastMessage = $this->messages()->latest()->first();

        return [
            'id' => $this->inquiry_code,
            'patientId' => $this->patient_code,
            'department' => $this->department,
            'message' => $lastMessage?->message ?? '',
            'date' => $this->created_at->format('M j'),
            'time' => $this->created_at->format('g:i A'),
            'isNew' => $this->is_new,
        ];
    }
}