<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $primaryKey = 'inquiry_id';

    protected $fillable = [
        'patient',
        'message',
        'status',
        'staff_id',
    ];

    public function messages()
    {
        return $this->hasMany(InquiryReply::class, 'inquiry_id', 'inquiry_id')
                    ->orderBy('created_at');
    }

    /**
     * Alias para sa 'messages' relation — ginagamit ito ng web controller
     * (Staff\InquiryController) na may Inquiry::with('replies').
     */
    public function replies()
    {
        return $this->messages();
    }

    public function toApiArray()
    {
        return [
            'id'         => (string) $this->inquiry_id,
            'patientId'  => $this->patient ?? '',
            'department' => $this->status ?? '',   // walang department column, status muna gamit
            'message'    => $this->message ?? '',
            'date'       => $this->created_at?->format('Y-m-d') ?? '',
            'time'       => $this->created_at?->format('h:i A') ?? '',
            'isNew'      => $this->status === 'Pending',   // walang is_new column, gamitin status bilang panghalili
        ];
    }
}