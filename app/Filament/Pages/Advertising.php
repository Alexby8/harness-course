<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\DTO\AdvertisingReport;
use App\DTO\AdvertisingUser;
use App\Enums\AcquisitionChannel;
use App\Filament\Resources\AdvertisingCampaignResource;
use App\Models\AdvertisingCampaign;
use App\Models\User;
use App\Services\AdvertisingAnalytics;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

final class Advertising extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Реклама';

    protected static ?string $title = 'Реклама';

    protected static ?string $slug = 'advertising';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.advertising';

    protected function getHeaderActions(): array
    {
        return [Action::make('campaigns')->label('Кампании и расходы')->url(AdvertisingCampaignResource::getUrl())];
    }

    #[Url]
    public string $period = '30';

    #[Url]
    public string $channel = 'paid';

    #[Url]
    public string $campaign = '';

    #[Url]
    public string $stage = 'all';

    #[Url]
    public string $search = '';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->is_admin;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['period', 'channel'], true)) {
            $this->campaign = '';
        }
        $this->resetPage();
    }

    public function selectCampaign(string $key): void
    {
        // Keys are hashes of the complete source tuple, never raw UTM in a URL/action.
        $this->campaign = preg_match('/\A[a-f0-9]{64}\z/D', $key) === 1 ? $key : '';
        $this->resetPage();
    }

    protected function report(): AdvertisingReport
    {
        return app(AdvertisingAnalytics::class)->report(in_array($this->period, ['7', '30', '90', '180'], true) ? (int) $this->period : 30);
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $report = $this->report();
        $channel = in_array($this->channel, ['all', 'paid', ...array_column(AcquisitionChannel::cases(), 'value')], true) ? $this->channel : 'paid';
        $selected = $report->select($channel, $this->campaign);
        $search = mb_strtolower(mb_substr(trim($this->search), 0, 100));
        $users = $selected->filter(fn (AdvertisingUser $user): bool => ($search === '' || str_contains(mb_strtolower($user->email), $search)) && match ($this->stage) {
            'verified' => $user->verified,
            'bot' => $user->bots > 0,
            'used' => $user->used === true,
            'active' => $user->active === true,
            'retained' => $user->retentionMatured && $user->retained === true,
            'pending' => ! $user->retentionMatured,
            'unknown' => $user->used === null,
            default => true,
        }
        )->values();
        $page = min(max(1, $this->getPage()), max(1, (int) ceil($users->count() / 25)));

        return [
            'report' => $report,
            'totals' => $report->summary($selected),
            'channels' => $report->channels(),
            'campaigns' => $report->campaigns($channel),
            'managedCampaigns' => AdvertisingCampaign::query()->get()->keyBy('match_key'),
            'weeks' => $report->weeks(),
            'users' => new LengthAwarePaginator($users->forPage($page, 25)->values(), $users->count(), 25, $page),
        ];
    }
}
