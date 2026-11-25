<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class HomeController extends Controller
{
    public function index(): View
    {
        $baseUrl = rtrim(config('app.url'), '/') . '/api';
        $jsonLd = $this->structuredData();

        return view('home', compact('baseUrl', 'jsonLd'));
    }

    private function structuredData(): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'BotGate',
            'applicationCategory' => 'DeveloperApplication',
            'operatingSystem' => 'Web',
            'description' => 'Прокси-сервис для Telegram Bot API. Позволяет стабильно обращаться к Telegram из России через единый шлюз с авторизацией по API-ключу и поддержкой webhook.',
            'url' => config('app.url'),
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'RUB'],
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
