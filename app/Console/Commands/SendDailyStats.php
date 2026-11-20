<?php

namespace App\Console\Commands;

use App\Mail\DailyStatsMail;
use App\Models\ArticleView;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyStats extends Command
{
    protected $signature = 'stats:daily';

    protected $description = 'Отправить модераторам статистику за сегодня: просмотры статей и новые комментарии';

    public function handle(): int
    {
        $views = ArticleView::query()->whereDate('created_at', today())->count();

        $comments = Comment::query()->whereDate('created_at', today())->count();

        // просмотры по статьям: название => количество
        $perArticle = ArticleView::query()
            ->whereDate('created_at', today())
            ->whereNotNull('article_id')
            ->with('article:id,name')
            ->get()
            ->groupBy('article_id')
            ->map(fn ($group) => [
                'name' => $group->first()->article?->name ?? 'Удалённая статья',
                'views' => $group->count(),
            ])
            ->sortByDesc('views')
            ->values();

        $emails = User::moderatorEmails();

        foreach ($emails as $email) {
            Mail::to($email)->send(new DailyStatsMail($views, $comments, $perArticle, today()->format('d.m.Y')));
        }

        $this->info("Статистика отправлена ({$emails->count()} получ.): просмотров {$views}, комментариев {$comments}");

        return self::SUCCESS;
    }
}
