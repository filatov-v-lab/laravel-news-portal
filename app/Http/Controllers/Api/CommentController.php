<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Support\ArticleCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Article $article): JsonResponse
    {
        $this->authorize('create', Comment::class);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $comment = $article->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'approved' => false,
        ]);

        return response()->json([
            'message' => 'Комментарий отправлен и ожидает модерации',
            'comment' => $comment,
        ], 201);
    }

    public function moderate(): JsonResponse
    {
        $this->authorize('moderate', Comment::class);

        return response()->json(
            Comment::query()->with(['user:id,name', 'article:id,name'])->where('approved', false)->latest()->get()
        );
    }

    public function accept(Comment $comment): JsonResponse
    {
        $this->authorize('moderate', Comment::class);

        $comment->update(['approved' => true]);
        ArticleCache::forgetShow($comment->article_id);

        return response()->json(['message' => 'Комментарий принят', 'comment' => $comment]);
    }

    public function reject(Comment $comment): JsonResponse
    {
        $this->authorize('moderate', Comment::class);

        $comment->delete();

        return response()->json(['message' => 'Комментарий отклонён']);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();
        ArticleCache::forgetShow($comment->article_id);

        return response()->json(['message' => 'Комментарий удалён']);
    }
}
