<?php

namespace App\Http\Controllers;

use App\Models\Staff\InquiryReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatAttachmentController extends Controller
{
    public function show(Request $request, InquiryReply $reply)
    {
        $user = $request->user();
        if (!$user && $request->bearerToken()) $user = Auth::guard('sanctum')->user();
        $staff = $user && in_array($user->user_role, ['Admin', 'Staff'], true) && $user->status === 'active';
        // A supplied conversation ID is not proof of ownership. Grant patient
        // access only to reply IDs successfully uploaded in this browser session.
        $owner = $request->hasSession() && in_array((int) $reply->reply_id, $request->session()->get('chat_attachment_ids', []), true);
        abort_unless($staff || $owner, 403);
        abort_unless(preg_match('#\Achat-attachments/([a-zA-Z0-9._-]+)\z#D', (string) $reply->attachment_path, $matches), 404);
        $file = $matches[1];
        abort_if($file === '.' || $file === '..', 404);
        $disk = Storage::disk('chat_attachments');
        abort_unless($disk->exists($file), 404);
        $mime = $disk->mimeType($file);
        $inline = in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
        $name = basename(str_replace('\\', '/', $reply->attachment_name ?: $file));
        $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name) ?: 'attachment';
        return $disk->response($file, $name, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
            'Referrer-Policy' => 'no-referrer',
        ], $inline ? 'inline' : 'attachment');
    }
}
