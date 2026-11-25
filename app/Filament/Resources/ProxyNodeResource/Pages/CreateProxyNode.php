<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProxyNodeResource\Pages;

use App\Enums\ProxyNodeStatus;
use App\Filament\Resources\ProxyNodeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProxyNode extends CreateRecord
{
    protected static string $resource = ProxyNodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['url'] = rtrim($data['url'], '/');
        $data['status'] = ProxyNodeStatus::Unknown;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
