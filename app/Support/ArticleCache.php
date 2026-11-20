<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Facades\Cache;

/**
 * Ключи кэша статей и очистка кэша списка (с пагинацией).
 */
class ArticleCache
{
    public const PER_PAGE = 6;

    public static function indexKey(int $page): string
    {
        return "articles.page.{$page}";
    }

    public static function showKey(int $articleId): string
    {
        return "article.{$articleId}";
    }

    /** Сбросить кэш всех страниц списка (включая пагинацию). */
    public static function forgetIndex(): void
    {
        $pages = (int) ceil(Article::query()->count() / self::PER_PAGE) + 1;

        for ($page = 1; $page <= $pages; $page++) {
            Cache::forget(self::indexKey($page));
        }
    }

    public static function forgetShow(int $articleId): void
    {
        Cache::forget(self::showKey($articleId));
    }
}
