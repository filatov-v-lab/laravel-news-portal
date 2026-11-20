<?php

namespace App\Jobs;

use App\Mail\NewArticleMail;
use App\Models\Article;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Долгое задание: рассылка писем модераторам о новой статье.
 * Раньше письма отправлялись прямо в контроллере, теперь — в очереди.
 */
class VeryLongJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Если статью успели удалить до запуска задания — тихо пропускаем. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Article $article)
    {
    }

    public function handle(): void
    {
        foreach (User::moderatorEmails() as $email) {
            Mail::to($email)->send(new NewArticleMail($this->article));
        }
    }
}
