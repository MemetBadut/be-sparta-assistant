<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateTicketRequest;
use App\Http\Resources\TicketActivityResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\Tickets\TicketActivityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class AdminTicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ticket::query()->with(['user', 'technician']);
        $search = $request->string('search')->toString();
        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('issue_title', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }
        foreach (['category', 'status', 'priority'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return TicketResource::collection($query->latest('id')->paginate(20));
    }

    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        return new TicketResource($ticket->load(['user', 'technician', 'attachments']));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket, TicketActivityService $activities): TicketResource
    {
        Gate::authorize('update', $ticket);

        try {
            return new TicketResource($activities->update($ticket, $request->user(), $request->validated()));
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }
    }

    public function activities(Ticket $ticket): AnonymousResourceCollection
    {
        Gate::authorize('viewActivities', $ticket);

        return TicketActivityResource::collection($ticket->activities()->paginate(30));
    }
}
