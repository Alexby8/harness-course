<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Mail\Message;
use Throwable;

final class TestMailCommand extends Command
{
    protected $signature = 'botgate:test-mail
        {email : Email получателя тестового письма}
        {--mailer= : Настроенный mailer; по умолчанию используется mail.default}';

    protected $description = 'Отправляет одно тестовое письмо синхронно, без очереди';

    public function handle(Factory $mail): int
    {
        $email = $this->argument('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Некорректный email получателя.');

            return self::INVALID;
        }

        $mailer = $this->option('mailer') ?? config('mail.default');

        if (! is_string($mailer) || $mailer === '') {
            $this->error('Не задан mailer.');

            return self::INVALID;
        }

        $transport = config("mail.mailers.{$mailer}.transport");

        if (! is_string($transport) || $transport === '') {
            $this->error('У выбранного mailer не настроен транспорт.');

            return self::INVALID;
        }

        if (in_array($transport, ['log', 'array', 'failover', 'roundrobin'], true)) {
            $this->error('Для проверки выберите прямой транспорт отправки через --mailer (например, --mailer=smtp).');

            return self::INVALID;
        }

        $subject = 'BotGate mail test '.gmdate('Y-m-d H:i:s').' UTC';
        $body = "Тестовое письмо BotGate.\nСервер: ".(gethostname() ?: 'unknown')."\n{$subject}\n";

        try {
            $sent = $mail->mailer($mailer)->raw($body, function (Message $message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });
        } catch (Throwable $exception) {
            // Transport errors may contain credentials or an SMTP conversation.
            $this->error('Ошибка отправки: '.$exception::class.'. Подробности скрыты, чтобы не раскрыть секреты.');

            return self::FAILURE;
        }

        if ($sent === null) {
            $this->error('Отправка отменена: транспорт не подтвердил передачу письма.');

            return self::FAILURE;
        }

        $this->info('Письмо передано почтовому транспорту.');
        $this->line('Тема: '.$subject);
        $this->line('Проверьте входящие и спам: получение письма ещё не подтверждено.');

        return self::SUCCESS;
    }
}
