<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateNotification extends CreateRecord
{
    protected static string $resource = NotificationResource::class;

    protected function afterCreate(): void
    {
        $recipients = $this->data['recipients'] ?? 'all';

        $users = match ($recipients) {
            'admins' => User::where('is_admin', true)->get(),
            'all' => User::all(),
            default => User::all(),
        };

        $notification = $this->getRecord();

        if (! $notification instanceof Notification) {
            throw new \LogicException('Unexpected notification record type.');
        }

        $userData = $users->mapWithKeys(fn ($user) => [
            $user->id => ['is_read' => false, 'created_at' => now()]
        ])->toArray();

        $notification->users()->attach($userData);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
