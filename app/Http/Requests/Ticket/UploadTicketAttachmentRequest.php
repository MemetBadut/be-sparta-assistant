<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UploadTicketAttachmentRequest extends FormRequest
{
    public const MAX_KB = 4096;

    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'log', 'docx', 'xlsx'])
                    ->max(self::MAX_KB),
            ],
        ];
    }
}
