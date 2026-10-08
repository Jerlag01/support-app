<?php

namespace App\Http\Controllers;

use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function show(Request $request, TicketAttachment $attachment): StreamedResponse
    {
        $message = $attachment->message;

        $this->authorize('view', $message->ticket);
        abort_if($message->is_internal && ! $request->user()->isStaff(), 404);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
