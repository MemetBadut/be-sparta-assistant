<?php

namespace App\Http\Requests\Admin;

use App\Enums\ArticleStatus;
use App\Enums\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateArticleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->canManage() === true; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(Category::values())],
            'symptoms' => ['required', 'string', 'max:10000'],
            'keywords' => ['nullable', 'string', 'max:10000'],
            'problem_description' => ['required', 'string', 'max:10000'],
            'expected_result' => ['required', 'string', 'max:10000'],
            'status' => ['required', 'string', Rule::in(array_map(static fn (ArticleStatus $s): string => $s->value, ArticleStatus::cases()))],
        ];
    }
}
