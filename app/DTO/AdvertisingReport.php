<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\AcquisitionChannel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class AdvertisingReport
{
    /** @param Collection<int, AdvertisingUser> $users */
    public function __construct(public Collection $users, public CarbonImmutable $start, public CarbonImmutable $end) {}

    /** @return Collection<int, AdvertisingUser> */
    public function select(string $channel = 'paid', string $campaign = ''): Collection
    {
        return $this->users->filter(fn (AdvertisingUser $user): bool => ($channel === 'all' || ($channel === 'paid' ? $user->source->channel->isAdvertising() : $user->source->channel->value === $channel))
            && ($campaign === '' || $user->source->key() === $campaign)
        )->values();
    }

    /**
     * @param  Collection<int, AdvertisingUser>  $users
     * @return array<string, int>
     */
    public function summary(Collection $users): array
    {
        return [
            'registered' => $users->count(),
            'verified' => $users->filter(fn (AdvertisingUser $user): bool => $user->verified)->count(),
            'bots' => $users->filter(fn (AdvertisingUser $user): bool => $user->bots > 0)->count(),
            'used' => $users->filter(fn (AdvertisingUser $user): bool => $user->used === true)->count(),
            'usage_unknown' => $users->filter(fn (AdvertisingUser $user): bool => $user->used === null)->count(),
            'active' => $users->filter(fn (AdvertisingUser $user): bool => $user->active === true)->count(),
            'active_unknown' => $users->filter(fn (AdvertisingUser $user): bool => $user->active === null)->count(),
            'eligible' => $users->filter(fn (AdvertisingUser $user): bool => $user->retentionMatured)->count(),
            'retained' => $users->filter(fn (AdvertisingUser $user): bool => $user->retentionMatured && $user->retained === true)->count(),
            'retention_unknown' => $users->filter(fn (AdvertisingUser $user): bool => $user->retentionMatured && $user->retained === null)->count(),
            'pending' => $users->filter(fn (AdvertisingUser $user): bool => ! $user->retentionMatured)->count(),
        ];
    }

    /** @return list<array{channel: AcquisitionChannel, totals: array<string, int>}> */
    public function channels(): array
    {
        return array_map(fn (AcquisitionChannel $channel): array => [
            'channel' => $channel,
            'totals' => $this->summary($this->select($channel->value)),
        ], AcquisitionChannel::cases());
    }

    /** @return list<array{source: AdvertisingSource, totals: array<string, int>}> */
    public function campaigns(string $channel): array
    {
        return array_values($this->select($channel)->groupBy(fn (AdvertisingUser $user): string => $user->source->key())
            ->map(fn (Collection $users): array => [
                'source' => $users->firstOrFail()->source,
                'totals' => $this->summary($users),
            ])->sortByDesc('totals.registered')->all());
    }

    /** @return list<array{label: string, paid: int, other: int, unknown: int, total: int}> */
    public function weeks(): array
    {
        $weeks = [];
        for ($date = $this->start->startOfWeek(); $date->lte($this->end); $date = $date->addWeek()) {
            $users = $this->users->filter(fn (AdvertisingUser $user): bool => $user->registeredAt->gte($date) && $user->registeredAt->lt($date->addWeek()));
            $paid = $users->filter(fn (AdvertisingUser $user): bool => $user->source->channel->isAdvertising())->count();
            $unknown = $users->filter(fn (AdvertisingUser $user): bool => $user->source->channel === AcquisitionChannel::Unknown)->count();
            $from = $date->max($this->start);
            $to = $date->addDays(6)->min($this->end);
            $weeks[] = ['label' => $from->format('d.m').'–'.$to->format('d.m'), 'paid' => $paid, 'other' => $users->count() - $paid - $unknown, 'unknown' => $unknown, 'total' => $users->count()];
        }

        return $weeks;
    }
}
