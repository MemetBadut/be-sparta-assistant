<?php

namespace App\Http\Requests\Ticket;

use App\Enums\Category;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'division' => ['required', 'string', 'max:255'],
            'issue_title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', 'string', Rule::in(Category::values())],
            'device_code' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'string', Rule::in(array_map(static fn (Priority $p): string => $p->value, Priority::cases()))],
            'troubleshooting_result_id' => ['nullable', 'integer', 'exists:troubleshooting_results,id'],
            'troubleshooting_history' => ['nullable', 'array'],
            'troubleshooting_history.*' => ['string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $category = (string) $this->input('category');
            if (Category::requiresDeviceCode($category) && $this->filled('device_code') === false) {
                $validator->errors()->add('device_code', 'A device code is required for device-related categories.');
            }
        });
    }
}
