<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

final class TestMailCommandTest extends TestCase
{
    private ArrayTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => ['transport' => 'smtp'],
            'mail.from.address' => 'sender@example.test',
            'mail.from.name' => 'BotGate',
        ]);

        $this->transport = new ArrayTransport;
        $this->app->make(MailManager::class)->extend('smtp', fn (): ArrayTransport => $this->transport);
    }

    public function test_it_sends_one_plain_text_message_with_the_configured_sender(): void
    {
        $this->artisan('botgate:test-mail', ['email' => 'recipient@example.test'])
            ->expectsOutput('Письмо передано почтовому транспорту.')
            ->assertSuccessful();

        $this->assertCount(1, $this->transport->messages());
        $message = $this->transport->messages()->sole()->getOriginalMessage();
        $this->assertInstanceOf(Email::class, $message);
        $this->assertSame('recipient@example.test', $message->getTo()[0]->getAddress());
        $this->assertSame('sender@example.test', $message->getFrom()[0]->getAddress());
        $this->assertStringStartsWith('BotGate mail test ', $message->getSubject());
        $this->assertStringContainsString('Тестовое письмо BotGate.', $message->getTextBody());
    }

    public function test_it_rejects_invalid_addresses_without_sending(): void
    {
        $this->artisan('botgate:test-mail', ['email' => "recipient@example.test\r\nBcc: other@example.test"])
            ->expectsOutput('Некорректный email получателя.')
            ->assertExitCode(2);

        $this->assertCount(0, $this->transport->messages());
    }

    #[DataProvider('nonDirectMailers')]
    public function test_it_does_not_claim_delivery_for_local_or_composite_transports(string $mailer): void
    {
        config(['mail.default' => $mailer]);

        $this->artisan('botgate:test-mail', ['email' => 'recipient@example.test'])
            ->assertExitCode(2);

        $this->assertCount(0, $this->transport->messages());
    }

    /** @return array<string, array{string}> */
    public static function nonDirectMailers(): array
    {
        return [
            'log' => ['log'],
            'array' => ['array'],
            'failover with log fallback' => ['failover'],
            'roundrobin' => ['roundrobin'],
        ];
    }

    public function test_it_can_explicitly_select_smtp_without_changing_the_default(): void
    {
        config(['mail.default' => 'log']);

        $this->artisan('botgate:test-mail', ['email' => 'recipient@example.test', '--mailer' => 'smtp'])
            ->assertSuccessful();

        $this->assertSame('log', config('mail.default'));
        $this->assertCount(1, $this->transport->messages());
    }

    public function test_it_rejects_an_unknown_mailer(): void
    {
        $this->artisan('botgate:test-mail', ['email' => 'recipient@example.test', '--mailer' => 'missing'])
            ->expectsOutput('У выбранного mailer не настроен транспорт.')
            ->assertExitCode(2);

        $this->assertCount(0, $this->transport->messages());
    }

    public function test_a_cancelled_message_is_not_reported_as_sent(): void
    {
        Event::listen(MessageSending::class, fn (): bool => false);

        $this->artisan('botgate:test-mail', ['email' => 'recipient@example.test'])
            ->expectsOutput('Отправка отменена: транспорт не подтвердил передачу письма.')
            ->assertFailed();

        $this->assertCount(0, $this->transport->messages());
    }

    public function test_transport_failures_do_not_expose_credentials_even_in_verbose_mode(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())->method('send')
            ->willThrowException(new RuntimeException('smtp://user:secret-password@example.test'));
        $this->app->make(MailManager::class)->extend('smtp', fn (): TransportInterface => $transport);

        $this->artisan('botgate:test-mail', ['email' => 'recipient@example.test', '-vvv' => true])
            ->expectsOutput('Ошибка отправки: RuntimeException. Подробности скрыты, чтобы не раскрыть секреты.')
            ->doesntExpectOutputToContain('secret-password')
            ->assertFailed();
    }
}
