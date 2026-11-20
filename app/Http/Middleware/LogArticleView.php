<?php

namespace App\Http\Middleware;

use App\Models\Article;
use App\Models\ArticleView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Сохраняет в таблице article_views каждый запрос на просмотр статьи.
 */
class LogArticleView
{
    public function handle(Request $request, Closure $next): Response
    {
        $article = $request->route('article');

        ArticleView::create([
            'article_id' => $article instanceof Article ? $article->id : $article,
            'url' => $request->fullUrl(),
        ]);

        return $next($request);
    }
}
