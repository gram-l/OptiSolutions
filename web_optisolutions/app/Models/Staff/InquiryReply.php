<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

/**
 * A single message in an inquiry's reply thread (Staff/Admin/Patient),
 * stored in the `inquiry_replies` table. See Inquiry::replies().
 */
class InquiryReply extends Model
{
    protected $table = 'inquiry_replies';
    protected $primaryKey = 'reply_id';

    protected $fillable = [
        'inquiry_id',
        'user_id',
        'sender',
        'is_staff',
        'message',
        'attachment_path',
        'attachment_name',
    ];

    protected $casts = [
        'is_staff' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (InquiryReply $reply) {
            if (!array_key_exists('is_staff', $reply->getAttributes())) {
                $reply->is_staff = strtolower((string) $reply->sender) !== 'patient';
            }
        });
    }

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id', 'inquiry_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? asset('storage/' . $this->attachment_path) : null;
    }

    public function toApiArray(): array
    {
        return [
            'replyId'        => $this->reply_id,
            'inquiryId'      => $this->inquiry_id,
            'sender'         => $this->sender,
            'isStaff'        => (bool) $this->is_staff,
            'message'        => $this->message,
            'attachmentUrl'  => $this->attachment_url,
            'attachmentName' => $this->attachment_name,
            'time'           => $this->created_at?->format('g:i A'),
        ];
    }
}