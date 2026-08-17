<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::values());
    }
}
