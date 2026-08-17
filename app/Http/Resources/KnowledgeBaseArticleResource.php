<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeBaseArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category->value,
            'symptoms' => $this->symptoms,
            'keywords' => $this->keywords,
            'problem_description' => $this->problem_description,
            'solution_steps' => $this->solution_steps,
            'expected_result' => $this->expected_result,
            'status' => $this->status->value,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
