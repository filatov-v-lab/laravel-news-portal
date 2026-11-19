<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPortalTest extends TestCase
{
    use RefreshDatabase;

    private function seedUsers(): array
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);

        return [
            User::where('email', 'moderator@example.com')->first(),
            User::where('email', 'reader@example.com')->first(),
        ];
    }

    public function test_public_pages_open_and_footer_has_author(): void
    {
        foreach (['/', '/about', '/contacts', '/articles', '/login', '/signin'] as $url) {
            $this->get($url)->assertOk()->assertSee('Филатов Владимир Андреевич, 257-321');
        }
    }

    public function test_gallery_shows_item_from_json_and_404_for_missing(): void
    {
        $this->get('/galery/0')->assertOk();
        $this->get('/galery/999')->assertNotFound();
    }

    public function test_registration_validates_and_creates_reader(): void
    {
        $this->seed(RoleSeeder::class);

        $this->post('/signin', ['name' => '', 'email' => 'x', 'password' => '1'])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->post('/signin', ['name' => 'Иван', 'email' => 'ivan@example.com', 'password' => 'secret1'])
            ->assertRedirect('/login');

        $this->assertTrue(User::where('email', 'ivan@example.com')->first()->isReader());
    }

    public function test_reader_cannot_create_article_but_moderator_can(): void
    {
        [$moderator, $reader] = $this->seedUsers();
        $payload = [
            'date' => '2026-11-01',
            'name' => 'Тестовая статья',
            'preview_image' => 'preview.jpg',
            'full_image' => 'full.jpeg',
            'desc' => 'Достаточно длинный текст статьи',
        ];

        // запрет прав в проекте оформлен как redirect back + flash-сообщение (bootstrap/app.php)
        $this->actingAs($reader)->post('/articles', $payload)
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('articles', ['name' => 'Тестовая статья']);

        $this->actingAs($moderator)->post('/articles', $payload)->assertRedirect();
        $this->assertDatabaseHas('articles', ['name' => 'Тестовая статья']);
    }

    public function test_comment_needs_moderation(): void
    {
        [$moderator, $reader] = $this->seedUsers();
        $article = Article::factory()->create();

        $this->actingAs($reader)
            ->post("/articles/{$article->id}/comments", ['body' => 'Отличная статья'])
            ->assertSessionHas('success');

        $comment = Comment::first();
        $this->assertFalse($comment->approved);

        $this->actingAs($reader)->get('/comments/moderate')
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->actingAs($moderator)->get('/comments/moderate')->assertOk()->assertSee('Отличная статья');

        $this->actingAs($moderator)->patch("/comments/{$comment->id}/accept");
        $this->assertTrue($comment->fresh()->approved);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/articles/create')->assertRedirect('/login');
        $this->get('/comments/moderate')->assertRedirect('/login');
    }

    public function test_articles_are_paginated(): void
    {
        Article::factory(8)->create();

        $this->get('/articles')->assertOk();
        $this->get('/articles?page=2')->assertOk();
    }
}
