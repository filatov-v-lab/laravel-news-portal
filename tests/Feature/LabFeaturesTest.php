<?php

namespace Tests\Feature;

use App\Events\NewArticleEvent;
use App\Jobs\VeryLongJob;
use App\Mail\DailyStatsMail;
use App\Mail\NewArticleMail;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\NewArticleNotification;
use App\Support\ArticleCache;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Проверки для ЛР 10+: push-события, очереди, уведомления, кэш,
 * планировщик (статистика) и REST API.
 */
class LabFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $moderator;

    protected User $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareUsers();
    }

    protected function prepareUsers(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);
        $this->moderator = User::where('email', 'moderator@example.com')->first();
        $this->reader = User::where('email', 'reader@example.com')->first();
    }

    private function articlePayload(array $override = []): array
    {
        return array_merge([
            'date' => '2026-11-01',
            'name' => 'Свежая новость',
            'preview_image' => 'preview.jpg',
            'full_image' => 'full.jpeg',
            'desc' => 'Достаточно длинный текст статьи',
        ], $override);
    }

    /**
     * Запрос к API от имени пользователя. В тестах охранник запоминает
     * пользователя между запросами, поэтому его нужно сбрасывать вручную.
     */
    private function asApi(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    // ---------- Push-событие ----------

    public function test_event_is_broadcast_on_public_channel_test(): void
    {
        $article = Article::factory()->create();
        $event = new NewArticleEvent($article);

        $channels = $event->broadcastOn();
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertSame('test', $channels[0]->name);
        $this->assertSame(
            ['article' => ['id' => $article->id, 'name' => $article->name]],
            $event->broadcastWith()
        );
    }

    public function test_creating_article_fires_event_queues_job_and_notifies_only_readers(): void
    {
        Event::fake([NewArticleEvent::class]);
        Queue::fake();
        Notification::fake();

        $this->actingAs($this->moderator)->post('/articles', $this->articlePayload())->assertRedirect();

        Event::assertDispatched(NewArticleEvent::class);
        Queue::assertPushed(VeryLongJob::class);
        Notification::assertSentTo($this->reader, NewArticleNotification::class);
        Notification::assertNotSentTo($this->moderator, NewArticleNotification::class);
    }

    // ---------- Очередь ----------

    public function test_very_long_job_mails_moderators(): void
    {
        Mail::fake();
        $article = Article::factory()->create();

        (new VeryLongJob($article))->handle();

        Mail::assertSent(NewArticleMail::class, fn ($mail) => $mail->hasTo('moderator@example.com'));
    }

    // ---------- Уведомления ----------

    public function test_notification_is_stored_listed_and_marked_read_on_open(): void
    {
        $this->actingAs($this->moderator)->post('/articles', $this->articlePayload(['name' => 'Уведомление-тест']));

        $this->assertSame(1, $this->reader->unreadNotifications()->count());
        $notification = $this->reader->unreadNotifications()->first();
        $this->assertSame('Уведомление-тест', $notification->data['name']);

        // в выпадающем списке есть название статьи
        $this->actingAs($this->reader)->get('/')->assertSee('Уведомление-тест');

        // переход по ссылке из списка отмечает уведомление прочитанным
        $this->actingAs($this->reader)
            ->get(route('articles.show', ['article' => $notification->data['article_id'], 'notification' => $notification->id]))
            ->assertOk();

        $this->assertSame(0, $this->reader->fresh()->unreadNotifications()->count());
    }

    // ---------- Кэш ----------

    public function test_index_is_cached_and_store_resets_it(): void
    {
        Article::factory(3)->create();

        $this->get('/articles')->assertOk();
        $this->assertTrue(Cache::has(ArticleCache::indexKey(1)));

        $this->actingAs($this->moderator)->post('/articles', $this->articlePayload());
        $this->assertFalse(Cache::has(ArticleCache::indexKey(1)));
    }

    public function test_article_page_cached_forever_and_comment_accept_refreshes_it(): void
    {
        $article = Article::factory()->create();

        $this->get("/articles/{$article->id}")->assertOk();
        $this->assertTrue(Cache::has(ArticleCache::showKey($article->id)));

        $comment = Comment::create([
            'article_id' => $article->id,
            'user_id' => $this->reader->id,
            'body' => 'Комментарий для проверки кэша',
            'approved' => false,
        ]);
        $this->get("/articles/{$article->id}")->assertDontSee('Комментарий для проверки кэша');

        $this->actingAs($this->moderator)->patch("/comments/{$comment->id}/accept");

        $this->get("/articles/{$article->id}")->assertSee('Комментарий для проверки кэша');
    }

    public function test_cached_pages_are_readable_from_database_cache_store(): void
    {
        // Laravel 13 не достаёт объекты из кэша без config/cache.php → serializable_classes.
        // Проверяем на настоящем хранилище database, а не на array.
        Article::factory(2)->create();
        config(['cache.default' => 'database']);

        $first = $this->get('/articles')->assertOk()->getContent();
        $second = $this->get('/articles')->assertOk()->getContent();
        $this->assertSame($first, $second);

        $article = Article::first();
        $this->get("/articles/{$article->id}")->assertOk();
        $this->get("/articles/{$article->id}")->assertOk()->assertSee($article->name);
    }

    public function test_update_and_destroy_flush_cache(): void
    {
        $article = Article::factory()->create();
        Cache::put('marker', 1);

        $this->actingAs($this->moderator)->put("/articles/{$article->id}", $this->articlePayload(['name' => 'Новое имя']));
        $this->assertFalse(Cache::has('marker'));

        Cache::put('marker', 1);
        $this->actingAs($this->moderator)->delete("/articles/{$article->id}");
        $this->assertFalse(Cache::has('marker'));
    }

    // ---------- Планировщик ----------

    public function test_article_views_are_logged_and_daily_stats_are_mailed(): void
    {
        Mail::fake();
        $article = Article::factory()->create(['name' => 'Популярная статья']);

        $this->get("/articles/{$article->id}");
        $this->get("/articles/{$article->id}");
        $this->assertSame(2, ArticleView::count());

        Comment::create(['article_id' => $article->id, 'user_id' => $this->reader->id, 'body' => 'Привет всем', 'approved' => false]);

        Artisan::call('stats:daily');

        Mail::assertSent(DailyStatsMail::class, function (DailyStatsMail $mail) {
            return $mail->hasTo('moderator@example.com')
                && $mail->views === 2
                && $mail->comments === 1;
        });
    }

    public function test_stats_command_is_scheduled_every_minute(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command, 'stats:daily'));

        $this->assertCount(1, $events);
        $this->assertSame('* * * * *', $events->first()->expression);
    }

    // ---------- REST API ----------

    public function test_api_register_login_and_user(): void
    {
        $this->postJson('/api/register', ['name' => 'Пётр', 'email' => 'petr@example.com', 'password' => 'secret1'])
            ->assertCreated();

        $this->postJson('/api/login', ['email' => 'petr@example.com', 'password' => 'bad'])->assertUnauthorized();

        $token = $this->postJson('/api/login', ['email' => 'petr@example.com', 'password' => 'secret1'])
            ->assertOk()
            ->json('token');

        $this->withToken($token)->getJson('/api/user')->assertOk()->assertJson(['role' => 'reader']);
    }

    public function test_api_validation_returns_422_json(): void
    {
        $this->postJson('/api/register', ['name' => '', 'email' => 'x', 'password' => '1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_api_articles_public_read_and_protected_write(): void
    {
        $article = Article::factory()->create();

        $this->getJson('/api/articles')->assertOk()->assertJsonPath('data.0.id', $article->id);
        $this->getJson("/api/articles/{$article->id}")->assertOk()->assertJsonPath('name', $article->name);

        // гость
        $this->postJson('/api/articles', $this->articlePayload())->assertUnauthorized();

        // читатель
        $this->asApi($this->reader)->postJson('/api/articles', $this->articlePayload())->assertForbidden();

        // модератор
        Queue::fake();
        $this->asApi($this->moderator)->postJson('/api/articles', $this->articlePayload(['name' => 'Через API']))
            ->assertCreated()
            ->assertJsonPath('name', 'Через API');
        Queue::assertPushed(VeryLongJob::class);

        $this->asApi($this->moderator)->putJson("/api/articles/{$article->id}", $this->articlePayload(['name' => 'Изменено']))
            ->assertOk()->assertJsonPath('name', 'Изменено');
        $this->asApi($this->moderator)->deleteJson("/api/articles/{$article->id}")->assertOk();
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_api_comment_moderation_flow(): void
    {
        $article = Article::factory()->create();
        $this->asApi($this->reader)->postJson("/api/articles/{$article->id}/comments", ['body' => 'Через API'])
            ->assertCreated();
        $comment = Comment::first();

        $this->asApi($this->reader)->getJson('/api/comments/moderate')->assertForbidden();
        $this->asApi($this->moderator)->getJson('/api/comments/moderate')->assertOk()->assertJsonCount(1);
        $this->asApi($this->moderator)->patchJson("/api/comments/{$comment->id}/accept")->assertOk();

        $this->getJson("/api/articles/{$article->id}")->assertJsonPath('approved_comments.0.body', 'Через API');
    }

    public function test_api_logout_revokes_token(): void
    {
        $token = $this->reader->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertSame(0, $this->reader->tokens()->count());
    }
}
