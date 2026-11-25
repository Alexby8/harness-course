<?php

declare(strict_types=1);

use App\Http\Controllers\AcquisitionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Livewire\Dashboard\ApiLogs;
use App\Livewire\Dashboard\WebhookEvents;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/docs', [DocsController::class, 'index'])->name('docs');

Route::prefix('guides')->name('guides.')->group(function (): void {
    Route::get('/', [GuideController::class, 'index'])->name('index');
    Route::get('/telegram-bot-botfather', [GuideController::class, 'show'])->defaults('slug', 'telegram-bot-botfather')->name('telegram-bot-botfather');
    Route::get('/telegram-bot-launch', [GuideController::class, 'show'])->defaults('slug', 'telegram-bot-launch')->name('telegram-bot-launch');
    Route::get('/telegram-bot-business', [GuideController::class, 'show'])->defaults('slug', 'telegram-bot-business')->name('telegram-bot-business');
    Route::get('/telegram-bot-api-timeout', [GuideController::class, 'show'])->defaults('slug', 'telegram-bot-api-timeout')->name('telegram-timeout');
    Route::get('/telegram-webhook-not-working', [GuideController::class, 'show'])->defaults('slug', 'telegram-webhook-not-working')->name('telegram-webhook');
    Route::get('/telegram-bot-api-proxy', [GuideController::class, 'show'])->defaults('slug', 'telegram-bot-api-proxy')->name('telegram-proxy');
    Route::get('/laravel-telegram-notifications', [GuideController::class, 'show'])->defaults('slug', 'laravel-telegram-notifications')->name('laravel-notifications');
    Route::get('/php-telegram-bot-sdk', [GuideController::class, 'show'])->defaults('slug', 'php-telegram-bot-sdk')->name('php-sdk');
    Route::get('/telegram-bot-api-error-429', [GuideController::class, 'show'])->defaults('slug', 'telegram-bot-api-error-429')->name('telegram-rate-limit');
    Route::get('/telegram-chat-not-found', [GuideController::class, 'show'])->defaults('slug', 'telegram-chat-not-found')->name('telegram-chat-not-found');
    Route::get('/telegram-cant-parse-entities', [GuideController::class, 'show'])->defaults('slug', 'telegram-cant-parse-entities')->name('telegram-parse-entities');
    Route::get('/telegram-bot-duplicate-messages', [GuideController::class, 'show'])->defaults('slug', 'telegram-bot-duplicate-messages')->name('telegram-duplicates');
    Route::get('/telegram-bot-files-not-sending', [GuideController::class, 'show'])->defaults('slug', 'telegram-bot-files-not-sending')->name('telegram-files');
    Route::get('/bitrix-telegram-order-notifications', [GuideController::class, 'show'])->defaults('slug', 'bitrix-telegram-order-notifications')->name('bitrix-notifications');
    Route::get('/{slug}', [GuideController::class, 'show'])->name('show');
    Route::post('/{slug}/view', [GuideController::class, 'recordView'])
        ->middleware('throttle:60,1,guide-views:')
        ->name('record-view');
});

Route::get('/cookie-consent', [CookieConsentController::class, 'show'])->name('cookie-consent.show');
Route::post('/acquisition', [AcquisitionController::class, 'store'])->middleware('throttle:60,1')->name('acquisition.store');
Route::delete('/acquisition', [AcquisitionController::class, 'destroy'])->middleware('throttle:60,1')->name('acquisition.destroy');
Route::post('/cookie-consent', [CookieConsentController::class, 'store'])
    ->middleware('throttle:30,1')->name('cookie-consent.store');

// SEO
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);

// Auth
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Email verification
Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Dashboard
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/api-key', [DashboardController::class, 'apiKey'])->name('dashboard.api-key');
    Route::get('/dashboard/api-logs', ApiLogs::class)->name('dashboard.api-logs');
    Route::get('/dashboard/webhook-events', WebhookEvents::class)->name('dashboard.webhook-events');
    Route::post('/dashboard/reissue-api-key', [DashboardController::class, 'reissueApiKey'])->name('api-key.reissue');
    Route::post('/notifications/{notificationId}/dismiss', [DashboardController::class, 'dismissNotification'])->name('notifications.dismiss');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');

    Route::get('/bots/create', [BotController::class, 'create'])->name('bots.create');
    Route::post('/bots', [BotController::class, 'store'])->name('bots.store');
    Route::get('/bots/{publicId}', [BotController::class, 'show'])->name('bots.show');
    Route::get('/bots/{publicId}/edit', [BotController::class, 'edit'])->name('bots.edit');
    Route::put('/bots/{publicId}', [BotController::class, 'update'])->name('bots.update');
    Route::post('/bots/{publicId}/disable', [BotController::class, 'disable'])->name('bots.disable');
    Route::post('/bots/{publicId}/enable', [BotController::class, 'enable'])->name('bots.enable');
    Route::post('/bots/{publicId}/activate-webhook', [BotController::class, 'activateWebhook'])->name('bots.activate-webhook');
    Route::post('/bots/{publicId}/deactivate-webhook', [BotController::class, 'deactivateWebhook'])->name('bots.deactivate-webhook');
    Route::post('/bots/{publicId}/resync-webhook', [BotController::class, 'resyncWebhook'])->name('bots.resync-webhook');
    Route::delete('/bots/{publicId}', [BotController::class, 'destroy'])->name('bots.destroy');
});
