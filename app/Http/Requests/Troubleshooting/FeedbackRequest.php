<?php

namespace App\Http\Requests\Troubleshooting;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['helpful' => ['required', 'boolean']]; }
}
