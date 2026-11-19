# Новостной портал (Laravel) — ЛР 1–9

Автор: Филатов Владимир Андреевич, группа 257-321.

Учебный проект по серверной веб-разработке: Blade-шаблоны, маршруты, контроллеры,
JSON-данные, регистрация и авторизация (Sanctum), статьи с CRUD и пагинацией,
роли и политики, почтовые уведомления, модерация комментариев.

## Требования

PHP 8.4+ (так требуют зависимости из `composer.lock`), Composer, расширения `pdo_sqlite`, `mbstring`.

## Запуск

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Сайт откроется на http://localhost:8000.

## Учётные записи из сидера

| Роль      | Email                  | Пароль   |
|-----------|------------------------|----------|
| Модератор | moderator@example.com  | password |
| Читатель  | reader@example.com     | password |

## Что реализовано по лабораторным

| ЛР | Тема | Где смотреть |
|----|------|--------------|
| 1 | Макет, страницы «О нас» и «Контакты» | `resources/views/layouts/app.blade.php`, `routes/web.php` |
| 2 | Вывод данных из `public/articles.json`, галерея | `MainController`, `home`, `gallery` |
| 3 | Регистрация, CSRF, валидация | `AuthController::create/registration`, `auth/signin` |
| 4 | Модель `Article`, миграция, фабрика, сидер | `app/Models`, `database/` |
| 5 | Пагинация и CRUD статей | `ArticleController`, `ArticleRequest`, `articles/*` |
| 6 | Вход/выход, Sanctum | `AuthController`, `auth:sanctum` в `routes/web.php` |
| 7 | Роли, политики, Gate | `Role`, `ArticlePolicy`, `CommentPolicy`, `AuthServiceProvider` |
| 8 | Почта при создании статьи | `NewArticleMail`, `.env` (`MAIL_*`) |
| 9 | Модерация комментариев | `CommentController`, `comments/moderate` |

## Почта (ЛР 8)

По умолчанию `MAIL_MAILER=log`: письма пишутся в `storage/logs/laravel.log`.
Для реальной отправки заполните в своём `.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.mail.ru
MAIL_PORT=465
MAIL_USERNAME=ваш_ящик
MAIL_PASSWORD=пароль_для_внешних_приложений
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=ваш_ящик
MAIL_MODERATOR_ADDRESS=адрес_получателя
```

Файл `.env` в репозиторий не коммитится.

## Тесты

```bash
php artisan test
```
