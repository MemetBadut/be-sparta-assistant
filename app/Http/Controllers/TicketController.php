<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\TroubleshootingResult;
use App\Services\Tickets\TicketNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return TicketResource::collection(
            $request->user()->tickets()->latest('id')->with('attachments')->paginate(config('pagination.per_page')),
        );
    }

    public function store(CreateTicketRequest $request, TicketNumberGenerator $numbers)
    {
        $user = $request->user();
        $result = null;

        if ($id = $request->input('troubleshooting_result_id')) {
            $result = TroubleshootingResult::where('id', $id)->where('user_id', $user->id)->first();
            abort_unless($result !== null, 422, 'The troubleshooting result is invalid.');
        }

        $payload = $result?->result_payload;
        $history = $payload['article']['steps'] ?? [];

        $ticket = $user->tickets()->create([
            'ticket_number' => $numbers->next(),
            'name' => $request->string('name')->toString(),
            'division' => $request->string('division')->toString(),
            'issue_title' => $request->string('issue_title')->toString(),
            'description' => $request->string('description')->toString(),
            'category' => $request->string('category')->toString(),
            'device_code' => $request->input('device_code'),
            'priority' => $request->string('priority')->toString(),
            'status' => 'Open',
            'kb_article_id' => $result?->selected_article_id,
            'troubleshooting_result_id' => $result?->id,
            'troubleshooting_history' => $history,
        ]);

        return (new TicketResource($ticket))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $ticketNumber): TicketResource
    {
        $ticket = $request->user()->tickets()->where('ticket_number', $ticketNumber)->firstOrFail();

        return new TicketResource($ticket->load('attachments'));
    }
}
