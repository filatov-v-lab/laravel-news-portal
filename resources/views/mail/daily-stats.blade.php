<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Статистика сайта</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <h1>Статистика за {{ $date }}</h1>

    <p><strong>Просмотров новостей:</strong> {{ $views }}</p>
    <p><strong>Новых комментариев:</strong> {{ $comments }}</p>

    @if ($perArticle->isNotEmpty())
        <h2>Просмотры по статьям</h2>
        <ul>
            @foreach ($perArticle as $row)
                <li>{{ $row['name'] }} — {{ $row['views'] }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
