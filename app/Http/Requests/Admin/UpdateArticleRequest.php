<?php

namespace App\Http\Requests\Admin;

class UpdateArticleRequest extends CreateArticleRequest
{
    public function rules(): array
    {
        return array_map(static function (array $rules): array {
            return array_values(array_filter($rules, static fn (string $rule): bool => $rule !== 'required'));
        }, parent::rules());
    }
}
