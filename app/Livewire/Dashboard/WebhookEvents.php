<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/** @extends JournalTable<WebhookEvent> */
final class WebhookEvents extends JournalTable
{
    /** @return Builder<WebhookEvent> */
    protected function recordsQuery(): Builder
    {
        $user = $this->currentUser();

        return WebhookEvent::query()
            ->select(['id', 'bot_id', 'created_at', 'status', 'target_url', 'attempts', 'last_http_status', 'last_error', 'delivered_at'])
            ->whereHas('bot', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);

                if ($this->bot !== '') {
                    $query->where('public_id', $this->bot);
                }
            })
            ->with('bot:id,public_id,name')
            ->when($this->status !== '', fn (Builder $query): Builder => $query->where('status', $this->status));
    }

    protected function sortableColumns(): array
    {
        return ['created_at'];
    }

    public function statusOptions(): array
    {
        $options = [];

        foreach (WebhookEventStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    public function render(): View
    {
        return view('livewire.dashboard.webhook-events', ['records' => $this->records(), 'bots' => $this->bots()])
            ->layout('layouts.app', ['title' => 'Webhook-события — BotGate']);
    }
}
