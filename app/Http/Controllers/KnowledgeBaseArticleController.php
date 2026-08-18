<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\CreateArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Http\Resources\KnowledgeBaseArticleResource;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KnowledgeBaseArticleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = KnowledgeBaseArticle::query();
        $search = $request->string('search')->toString();
        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }
        foreach (['category', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return KnowledgeBaseArticleResource::collection($query->latest('updated_at')->paginate(config('pagination.per_page')));
    }

    public function store(CreateArticleRequest $request): KnowledgeBaseArticleResource
    {
        return new KnowledgeBaseArticleResource(KnowledgeBaseArticle::create([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]));
    }

    public function show(KnowledgeBaseArticle $article): KnowledgeBaseArticleResource
    {
        return new KnowledgeBaseArticleResource($article);
    }

    public function update(UpdateArticleRequest $request, KnowledgeBaseArticle $article): KnowledgeBaseArticleResource
    {
        $article->update([...$request->validated(), 'updated_by' => $request->user()->id]);

        return new KnowledgeBaseArticleResource($article->fresh());
    }

    public function destroy(Request $request, KnowledgeBaseArticle $article)
    {
        abort_unless($request->boolean('confirm'), response()->json([
            'message' => 'Deletion requires confirmation.',
            'error_code' => 'CONFIRMATION_REQUIRED',
        ], 422));

        $article->delete();

        return response()->json(['message' => 'Article deleted.']);
    }
}
