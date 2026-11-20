<?php

namespace App\Notifications;

use App\Models\Article;
use Illuminate\Notifications\Notification;

/**
 * Уведомление читателям о новой статье (хранится в таблице notifications).
 */
class NewArticleNotification extends Notification
{
    public function __construct(public Article $article)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Данные, которые сохранятся в колонке data (вместо toArray).
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'article_id' => $this->article->id,
            'name' => $this->article->name,
        ];
    }
}
