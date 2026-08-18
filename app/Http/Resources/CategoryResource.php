<?php

namespace App\Http\Resources;

use App\Enums\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $category = $this->resource instanceof Category ? $this->resource : Category::from($this->resource);

        return [
            'id' => $category->value,
            'label' => $category->label(),
        ];
    }
}
