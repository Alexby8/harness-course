<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdvertisingCampaignResource\Pages;

use App\Filament\Resources\AdvertisingCampaignResource;
use App\Services\AdvertisingAnalytics;
use Filament\Resources\Pages\CreateRecord;

final class CreateAdvertisingCampaign extends CreateRecord
{
    protected static string $resource = AdvertisingCampaignResource::class;

    protected static ?string $title = 'Добавить рекламную кампанию';

    protected ?bool $hasDatabaseTransactions = true;

    public function mount(): void
    {
        parent::mount();
        $key = request()->query('source_key');
        if (! is_string($key) || preg_match('/\A[a-f0-9]{64}\z/D', $key) !== 1) {
            return;
        }
        foreach (app(AdvertisingAnalytics::class)->report(180)->campaigns('paid') as $item) {
            $source = $item['source'];
            if ($source->key() === $key) {
                $this->form->fill([
                    'channel' => $source->channel->value, 'utm_source' => $source->source, 'utm_medium' => $source->medium,
                    'utm_campaign' => $source->campaign, 'name' => $source->campaign, 'includes_vat' => true, 'archived' => false,
                ]);
                break;
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return AdvertisingCampaignResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
