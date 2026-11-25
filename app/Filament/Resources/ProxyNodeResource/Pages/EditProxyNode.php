<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProxyNodeResource\Pages;

use App\Filament\Resources\ProxyNodeResource;
use App\Models\ProxyNode;
use App\Models\User;
use App\Services\Proxy\ProxyNodeTelegramTester;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditProxyNode extends EditRecord
{
    protected static string $resource = ProxyNodeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! is_string($data['url'] ?? null)) {
            throw new \LogicException('Unexpected proxy node URL type.');
        }

        $data['url'] = rtrim($data['url'], '/');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof ProxyNode) {
            throw new \LogicException('Unexpected proxy node record type.');
        }

        return [
            Action::make('testTelegram')
                ->label('Отправить тест в Telegram')
                ->icon('heroicon-o-paper-airplane')
                ->authorize(function (): bool {
                    $user = auth()->user();

                    return $user instanceof User && $user->is_admin;
                })
                ->action(function (ProxyNodeTelegramTester $tester) use ($record): void {
                    $result = $tester->send($record->refresh());

                    Notification::make()
                        ->title($result->successful ? 'Тестовое сообщение отправлено' : 'Тест Telegram не выполнен')
                        ->body($result->message)
                        ->color($result->successful ? 'success' : 'danger')
                        ->send();
                }),
            DeleteAction::make()
                ->label('Удалить')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! $record->enabled),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
