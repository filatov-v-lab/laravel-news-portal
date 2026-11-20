<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Services\ArticlePublisher;
use App\Support\ArticleCache;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    public function __construct(private ArticlePublisher $publisher)
    {
    }

    public function index()
    {
        $this->authorize('viewAny', Article::class);

        $page = max(1, (int) request('page', 1));

        // Кэшируем каждую страницу списка (пагинация) отдельным ключом.
        $articles = Cache::remember(
            ArticleCache::indexKey($page),
            now()->addHour(),
            fn () => Article::query()
                ->orderByDesc('date')
                ->paginate(ArticleCache::PER_PAGE, ['*'], 'page', $page)
                ->withPath(route('articles.index'))
        );

        return view('articles.index', compact('articles'));
    }

    public function create()
    {
        $this->authorize('create', Article::class);

        return view('articles.create');
    }

    public function store(ArticleRequest $request)
    {
        $this->authorize('create', Article::class);

        // Создание статьи + письмо в очередь + push + уведомления читателям.
        $article = $this->publisher->publish($request->validated());

        // Список изменился — сбрасываем кэш главной страницы статей и всех страниц пагинации.
        ArticleCache::forgetIndex();

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'Статья создана. Письмо модераторам и уведомления отправляются в фоне');
    }

    public function show(Article $article)
    {
        $this->authorize('view', $article);

        // Перешли по ссылке из выпадающего списка уведомлений — отмечаем его прочитанным.
        if (($notificationId = request('notification')) && ($user = request()->user())) {
            $user->unreadNotifications()->where('id', $notificationId)->first()?->markAsRead();
        }

        // Статья вместе с одобренными комментариями кэшируется "навсегда".
        $article = Cache::rememberForever(
            ArticleCache::showKey($article->id),
            fn () => $article->load(['approvedComments.user:id,name'])
        );

        return view('articles.show', compact('article'));
    }

    public function edit(Article $article)
    {
        $this->authorize('update', $article);

        return view('articles.edit', compact('article'));
    }

    public function update(ArticleRequest $request, Article $article)
    {
        $this->authorize('update', $article);

        $article->update($request->validated());

        Cache::flush();

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'Статья обновлена');
    }

    public function destroy(Article $article)
    {
        $this->authorize('delete', $article);

        $article->delete();

        Cache::flush();

        return redirect()
            ->route('articles.index')
            ->with('success', 'Статья удалена');
    }
}
