<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ticket_number' => $this->ticket_number,
            'name' => $this->name,
            'division' => $this->division,
            'issue_title' => $this->issue_title,
            'description' => $this->description,
            'category' => $this->category->value,
            'device_code' => $this->device_code,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'assigned_technician' => $this->whenLoaded('technician', fn () => $this->technician?->name),
            'troubleshooting_history' => $this->troubleshooting_history,
            'resolution_notes' => $this->when(
                $request->user()?->canManage() || in_array($this->status->value, ['Resolved', 'Closed'], true),
                $this->resolution_notes,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
