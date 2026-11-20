<?php

namespace App\Events;

use App\Models\Article;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Push-уведомление о новой статье для всех, кто сейчас на сайте.
 * ShouldBroadcast: событие уходит в очередь и транслируется через Pusher.
 */
class NewArticleEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Article $article)
    {
    }

    /**
     * Публичный канал "test" (PrivateChannel заменён на Channel).
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('test'),
        ];
    }

    /**
     * Данные, которые получит Vue-компонент (article.article.id / .name).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'article' => [
                'id' => $this->article->id,
                'name' => $this->article->name,
            ],
        ];
    }
}
