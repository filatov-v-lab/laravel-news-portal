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
| Push | Онлайн-уведомление о новой статье (Pusher, Laravel Echo, Vue) | `app/Events/NewArticleEvent.php`, `resources/js/bootstrap.js`, `resources/js/components/App.vue` |
| Очереди | Отправка писем модераторам в очереди | `app/Jobs/VeryLongJob.php`, `QUEUE_CONNECTION=database` |
| Уведомления | Уведомления читателям в БД, выпадающий список в шапке | `app/Notifications/NewArticleNotification.php`, `layouts/app.blade.php` |
| Кэш | Кэширование списка и страницы статьи | `ArticleController`, `app/Support/ArticleCache.php`, `config/cache.php` |
| Планировщик | Ежеминутная статистика модераторам на почту | `app/Console/Commands/SendDailyStats.php`, `routes/console.php`, `LogArticleView` |
| REST API | API-версия сайта | `routes/api.php`, `app/Http/Controllers/Api/*` |

## Установка новых возможностей

Выполните один раз:

```bash
composer require pusher/pusher-php-server
npm install
npm run build
php artisan migrate
```

Без `npm run build` сайт работает, просто без push-уведомлений.

### Что запускать (каждое в своём терминале)

```bash
php artisan serve              # сайт
php artisan queue:work         # очередь: письма и push-события
php artisan schedule:work      # планировщик: статистика каждую минуту
npm run watch                  # (по желанию) пересборка js при правках
```

После изменений в PHP-коде перезапустите `queue:work`: воркер держит код в памяти.

### Push-уведомления (Pusher)

1. Создайте приложение на https://pusher.com (кластер `eu`) и скопируйте ключи.
2. В `.env` укажите `BROADCAST_CONNECTION=pusher` и значения `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER=eu`.
3. Выполните `php artisan config:clear` и `npm run build`.
4. Откройте сайт в двух окнах, в одном войдите модератором и создайте статью: во втором появится всплывающее уведомление.

Ошибка «connection refused» от vite решается так: закройте `npm run dev` и выполните `npm run build`.

### Уведомления в шапке

Уведомления получают только читатели. Войдите читателем после того, как модератор создал статью: в шапке появится «Уведомления» со счётчиком. При переходе по ссылке уведомление отмечается прочитанным.

### Кэш

`CACHE_STORE=database` (таблица `cache` создаётся миграцией). Список статей кэшируется по страницам, страница статьи с комментариями — навсегда. Кэш сбрасывается при создании, изменении, удалении статьи и при принятии комментария. В Laravel 13 классы, которые можно достать из кэша, перечислены в `config/cache.php` (`serializable_classes`).

### Планировщик

Команда `php artisan stats:daily` отправляет модераторам число просмотров статей и новых комментариев за сегодня. В `routes/console.php` она запускается каждую минуту. Для проверки по-быстрому: `php artisan stats:daily`, письмо появится в `storage/logs/laravel.log` (при `MAIL_MAILER=log`).

### REST API

Адреса начинаются с `/api`. В Postman/Insomnia добавляйте заголовок `Accept: application/json`.

| Метод | Адрес | Доступ |
|-------|-------|--------|
| POST | `/api/register` | все |
| POST | `/api/login` (возвращает `token`) | все |
| GET | `/api/articles`, `/api/articles/{id}` | все |
| POST | `/api/logout`, GET `/api/user` | с токеном |
| POST / PUT / DELETE | `/api/articles`, `/api/articles/{id}` | модератор |
| POST | `/api/articles/{id}/comments` | читатель/модератор |
| GET | `/api/comments/moderate` | модератор |
| PATCH / DELETE | `/api/comments/{id}/accept`, `/reject` | модератор |

Защищённые адреса требуют заголовок `Authorization: Bearer <token>`. Обычные веб-маршруты сайта остались как есть, API работает рядом с ними.

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
