<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Services\ArticlePublisher;
use App\Support\ArticleCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    public function __construct(private ArticlePublisher $publisher)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Article::class);

        return response()->json(
            Article::query()->orderByDesc('date')->paginate(ArticleCache::PER_PAGE)
        );
    }

    public function show(Article $article): JsonResponse
    {
        $this->authorize('view', $article);

        return response()->json($article->load('approvedComments.user'));
    }

    public function store(ArticleRequest $request): JsonResponse
    {
        $this->authorize('create', Article::class);

        $article = $this->publisher->publish($request->validated());

        ArticleCache::forgetIndex();

        return response()->json($article, 201);
    }

    public function update(ArticleRequest $request, Article $article): JsonResponse
    {
        $this->authorize('update', $article);

        $article->update($request->validated());

        Cache::flush();

        return response()->json($article);
    }

    public function destroy(Article $article): JsonResponse
    {
        $this->authorize('delete', $article);

        $article->delete();

        Cache::flush();

        return response()->json(['message' => 'Статья удалена']);
    }
}
