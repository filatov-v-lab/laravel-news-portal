<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Middleware\LogArticleView;
use Illuminate\Support\Facades\Route;

/*
| REST API. Все адреса начинаются с /api.
| Для запросов добавляйте заголовок  Accept: application/json
| Защищённые адреса требуют заголовок  Authorization: Bearer <token>
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/articles', [ArticleController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{article}', [ArticleController::class, 'update']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);

    Route::post('/articles/{article}/comments', [CommentController::class, 'store']);
    Route::get('/comments/moderate', [CommentController::class, 'moderate']);
    Route::patch('/comments/{comment}/accept', [CommentController::class, 'accept']);
    Route::delete('/comments/{comment}/reject', [CommentController::class, 'reject']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});

Route::get('/articles/{article}', [ArticleController::class, 'show'])->middleware(LogArticleView::class);
