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
}
