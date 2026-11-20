<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DailyStatsMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, array{name: string, views: int}>  $perArticle
     */
    public function __construct(
        public int $views,
        public int $comments,
        public Collection $perArticle,
        public string $date,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Статистика сайта за '.$this->date)
            ->view('mail.daily-stats');
    }
}
