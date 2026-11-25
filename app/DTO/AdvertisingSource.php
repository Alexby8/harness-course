<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\AcquisitionChannel;
use App\Models\UserAcquisition;

final readonly class AdvertisingSource
{
    public function __construct(
        public AcquisitionChannel $channel,
        public string $source,
        public string $medium,
        public string $campaign,
    ) {}

    public function key(): string
    {
        return hash('sha256', serialize([$this->channel->value, $this->source, $this->medium, $this->campaign]));
    }

    public static function fromAcquisition(?UserAcquisition $acquisition): self
    {
        // Never mix parameters of independent touches, even for a missing campaign.
        $touch = $acquisition?->getAttribute('last_tagged_touch') ?? $acquisition?->getAttribute('first_touch');
        $raw = is_array($touch) ? ($touch['parameters'] ?? []) : [];
        $parameters = [];
        if (is_array($raw)) {
            foreach ($raw as $name => $value) {
                if (is_string($name) && is_string($value)) {
                    $parameters[$name] = trim($value);
                }
            }
        }

        $source = mb_strtolower($parameters['utm_source'] ?? '');
        $medium = mb_strtolower($parameters['utm_medium'] ?? '');
        $paid = in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'paid_search', 'paid_social', 'cpm', 'cpv', 'display'], true);
        $referrer = is_array($touch) && is_string($touch['referrer_host'] ?? null) ? $touch['referrer_host'] : '';
        $channel = match (true) {
            ($parameters['yclid'] ?? '') !== '',
            $paid && in_array($source, ['yandex', 'ya', 'yandex.ru', 'yandex_direct', 'yandex-direct'], true) => AcquisitionChannel::YandexDirect,
            $paid => AcquisitionChannel::OtherAdvertising,
            $source !== '' || $referrer !== '' => AcquisitionChannel::OtherSources,
            default => AcquisitionChannel::Unknown,
        };

        return new self($channel, $source, $medium, $parameters['utm_campaign'] ?? '');
    }
}
