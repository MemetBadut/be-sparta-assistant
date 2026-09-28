<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ticket\UploadTicketAttachmentRequest;
use App\Http\Resources\TicketAttachmentResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function store(UploadTicketAttachmentRequest $request, string $ticketNumber)
    {
        $ticket = $request->user()->tickets()->where('ticket_number', $ticketNumber)->firstOrFail();

        $file = $request->file('file');
        $path = $file->storeAs(
            'attachments/'.$ticket->ticket_number,
            Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
            ['disk' => 'local'],
        );

        $attachment = $ticket->attachments()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'created_by' => $request->user()->id,
        ]);

        return (new TicketAttachmentResource($attachment))->response()->setStatusCode(201);
    }

    public function download(Request $request, string $ticketNumber, int $attachment): mixed
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();
        abort_unless($request->user()->canManage() || $ticket->user_id === $request->user()->id, 404);
        $file = $ticket->attachments()->whereKey($attachment)->firstOrFail();

        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ]);
    }
}
