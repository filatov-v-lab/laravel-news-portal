<?php

namespace App\Services;

use App\Events\NewArticleEvent;
use App\Jobs\VeryLongJob;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewArticleNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Единое место, где создаётся статья и запускается всё, что с этим связано.
 * Используется и веб-контроллером, и REST API-контроллером.
 */
class ArticlePublisher
{
    /**
     * @param  array<string, mixed>  $data  провалидированные поля статьи
     */
    public function publish(array $data): Article
    {
        $article = Article::create($data);

        // 1. Письмо модераторам — в очередь (ЛР «Очереди»).
        VeryLongJob::dispatch($article);

        // 2. Push-уведомление всем, кто сейчас на сайте (Pusher + Echo).
        event(new NewArticleEvent($article));

        // 3. Уведомление в БД — только читателям (ЛР «Уведомления»).
        $readers = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', Role::READER))
            ->get();

        Notification::send($readers, new NewArticleNotification($article));

        return $article;
    }
}
