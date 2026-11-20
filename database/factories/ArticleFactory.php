<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $images = [
            ['preview.jpg', 'full.jpeg'],
            ['preview_2.jpg', 'full_2.jpeg'],
        ];

        [$preview, $full] = fake()->randomElement($images);

        // Русский текст (realText из ru_RU), а не латинский lorem ipsum.
        $ru = fake('ru_RU');

        $titles = [
            'Открытие нового сезона', 'Итоги недели', 'Что нового в городе', 'Главное за сутки',
            'Тест-драйв спорткара', 'Как живут тигры зимой', 'Скорость и дорога', 'Погода на выходные',
            'Новости науки', 'Спорт: результаты дня', 'Культурная афиша', 'Интервью с экспертом',
            'Технологии будущего', 'Путешествие по России', 'Обзор рынка', 'Здоровье и отдых',
        ];

        return [
            'date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'name' => fake()->randomElement($titles),
            'preview_image' => $preview,
            'full_image' => $full,
            'short_desc' => $ru->realText(110),
            'desc' => $ru->realText(700),
        ];
    }
}
