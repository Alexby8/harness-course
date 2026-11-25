<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BotStatus;
use App\Models\Bot;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use JsonException;
use Throwable;

final class CallTelegramMethodCommand extends Command
{
    protected $signature = 'botgate:telegram:call
        {bot_public_id : Public ID бота из BotGate}
        {method : Метод Telegram Bot API, например sendMessage}
        {--params= : JSON-объект с параметрами Telegram Bot API; по умолчанию пустой объект}
        {--file=* : Файл в формате field=/absolute/path; опцию можно повторять}
        {--force : Выполнить реальный вызов без интерактивного подтверждения}';

    protected $description = 'Выполняет реальный smoke-вызов Telegram Bot API через Proxy Node Manager';

    public function handle(TelegramService $telegram): int
    {
        $publicId = $this->stringArgument('bot_public_id');
        $method = $this->stringArgument('method');

        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]{0,127}$/', $method)) {
            $this->error('Некорректное имя Telegram-метода.');

            return self::INVALID;
        }

        if (in_array($method, config('botgate.forbidden_methods', []), true)) {
            $this->error("Метод {$method} запрещён конфигурацией BotGate.");

            return self::FAILURE;
        }

        $bot = Bot::query()->where('public_id', $publicId)->first();

        if ($bot === null) {
            $this->error("Бот с public ID {$publicId} не найден.");

            return self::FAILURE;
        }

        if ($bot->status !== BotStatus::Active) {
            $this->error('Бот отключён. Smoke-вызов отменён.');

            return self::FAILURE;
        }

        try {
            $params = $this->parseParams();
            $files = $this->parseFiles();
        } catch (JsonException|\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $transport = $files === [] ? 'JSON' : 'multipart';
        $this->components->twoColumnDetail('Бот', "{$bot->name} ({$bot->public_id})");
        $this->components->twoColumnDetail('Метод', $method);
        $this->components->twoColumnDetail('Транспорт', $transport);

        if (! $this->option('force') && ! $this->confirm('Выполнить реальный запрос к Telegram?', false)) {
            $this->warn('Вызов отменён.');

            return self::SUCCESS;
        }

        try {
            $result = $files === []
                ? $telegram->proxyJson($bot->telegram_token_encrypted, $method, $params)
                : $telegram->proxyMultipart($bot->telegram_token_encrypted, $method, $params, $files);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Ошибка соединения при выполнении Telegram-запроса. Подробности записаны в лог.');

            return self::FAILURE;
        }

        $encodedResult = json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
        $this->line($encodedResult);

        if (($result['ok'] ?? false) !== true) {
            $this->error('Telegram вернул ошибку.');

            return self::FAILURE;
        }

        $this->info('Smoke-вызов выполнен успешно.');

        return self::SUCCESS;
    }

    private function stringArgument(string $name): string
    {
        $value = $this->argument($name);

        return is_string($value) ? $value : '';
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function parseParams(): array
    {
        $rawParams = $this->option('params');

        if ($rawParams === null) {
            $rawParams = '{}';
        }

        $params = json_decode($rawParams, true, 32, JSON_THROW_ON_ERROR);

        if (! is_array($params) || array_is_list($params) && $params !== []) {
            throw new \InvalidArgumentException('Опция --params должна содержать JSON-объект, а не список.');
        }

        return $params;
    }

    /**
     * @return array<string, array{path: string, filename: string, mime_type: ?string}>
     */
    private function parseFiles(): array
    {
        $fileOptions = $this->option('file');

        $files = [];

        foreach ($fileOptions as $fileOption) {
            if (! is_string($fileOption) || ! str_contains($fileOption, '=')) {
                throw new \InvalidArgumentException('Каждый --file должен иметь формат field=/absolute/path.');
            }

            [$field, $path] = explode('=', $fileOption, 2);

            if (! preg_match('/^[A-Za-z][A-Za-z0-9_]{0,127}$/', $field)) {
                throw new \InvalidArgumentException("Некорректное имя поля файла: {$field}");
            }

            if (isset($files[$field])) {
                throw new \InvalidArgumentException("Поле файла {$field} указано повторно.");
            }

            $resolvedPath = realpath($path);

            if ($resolvedPath === false || ! is_file($resolvedPath) || ! is_readable($resolvedPath)) {
                throw new \InvalidArgumentException("Файл недоступен для чтения: {$path}");
            }

            $mimeType = mime_content_type($resolvedPath);
            $files[$field] = [
                'path' => $resolvedPath,
                'filename' => basename($resolvedPath),
                'mime_type' => is_string($mimeType) ? $mimeType : null,
            ];
        }

        return $files;
    }
}
