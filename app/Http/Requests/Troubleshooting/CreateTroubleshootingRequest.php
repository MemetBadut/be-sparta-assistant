<?php

namespace App\Http\Requests\Troubleshooting;

use App\Enums\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTroubleshootingRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(Category::values())],
            'description' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }
}
