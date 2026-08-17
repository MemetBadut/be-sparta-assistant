<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\KnowledgeBaseArticleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TroubleshootingController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
RateLimiter::for('troubleshooting', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?? $request->ip()));
RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?? $request->ip()));

Route::middleware(['web', 'throttle:auth'])->prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::get('categories', CategoryController::class);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('profile', ProfileController::class);
    Route::post('troubleshooting', [TroubleshootingController::class, 'store'])->middleware('throttle:troubleshooting');
    Route::post('troubleshooting/{troubleshooting}/feedback', [TroubleshootingController::class, 'feedback']);
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/{ticketNumber}', [TicketController::class, 'show']);
    Route::post('tickets/{ticketNumber}/attachments', [AttachmentController::class, 'store'])->middleware('throttle:uploads');

    Route::prefix('admin')->middleware('can.manage')->group(function (): void {
        Route::get('dashboard', AdminDashboardController::class);
        Route::get('tickets', [AdminTicketController::class, 'index']);
        Route::get('tickets/{ticket}', [AdminTicketController::class, 'show']);
        Route::patch('tickets/{ticket}', [AdminTicketController::class, 'update']);
        Route::get('tickets/{ticket}/activities', [AdminTicketController::class, 'activities']);
        Route::get('articles', [KnowledgeBaseArticleController::class, 'index']);
        Route::post('articles', [KnowledgeBaseArticleController::class, 'store']);
        Route::get('articles/{article}', [KnowledgeBaseArticleController::class, 'show']);
        Route::patch('articles/{article}', [KnowledgeBaseArticleController::class, 'update']);
        Route::delete('articles/{article}', [KnowledgeBaseArticleController::class, 'destroy']);
    });
});

Route::model('ticket', \App\Models\Ticket::class);
Route::model('troubleshooting', \App\Models\TroubleshootingResult::class);
Route::model('article', \App\Models\KnowledgeBaseArticle::class);
Route::bind('ticket', fn (string $value) => \App\Models\Ticket::where('ticket_number', $value)->firstOrFail());
Route::bind('ticketNumber', fn (string $value) => $value);

Route::get('categories', CategoryController::class);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('profile', ProfileController::class);
    Route::post('troubleshooting', [TroubleshootingController::class, 'store']);
    Route::post('troubleshooting/{troubleshooting}/feedback', [TroubleshootingController::class, 'feedback']);
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/{ticketNumber}', [TicketController::class, 'show']);
    Route::post('tickets/{ticketNumber}/attachments', [AttachmentController::class, 'store']);

    Route::prefix('admin')->middleware('can.manage')->group(function (): void {
        Route::get('tickets', [AdminTicketController::class, 'index']);
        Route::get('tickets/{ticket}', [AdminTicketController::class, 'show']);
        Route::patch('tickets/{ticket}', [AdminTicketController::class, 'update']);
        Route::get('tickets/{ticket}/activities', [AdminTicketController::class, 'activities']);
        Route::get('articles', [KnowledgeBaseArticleController::class, 'index']);
        Route::post('articles', [KnowledgeBaseArticleController::class, 'store']);
        Route::get('articles/{article}', [KnowledgeBaseArticleController::class, 'show']);
        Route::patch('articles/{article}', [KnowledgeBaseArticleController::class, 'update']);
        Route::delete('articles/{article}', [KnowledgeBaseArticleController::class, 'destroy']);
    });
});

Route::model('ticket', \App\Models\Ticket::class);
Route::model('troubleshooting', \App\Models\TroubleshootingResult::class);
