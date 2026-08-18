<?php

namespace App\Http\Requests\Admin;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->canManage() === true; }

    public function rules(): array
    {
        return [
            'assigned_technician' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(array_map(static fn (TicketStatus $s): string => $s->value, TicketStatus::cases()))],
            'resolution_notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ];
    }
}
