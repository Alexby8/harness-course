<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\ApiRequestLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/** @extends JournalTable<ApiRequestLog> */
final class ApiLogs extends JournalTable
{
    /** @return Builder<ApiRequestLog> */
    protected function recordsQuery(): Builder
    {
        $user = $this->currentUser();

        return ApiRequestLog::query()
            ->select(['id', 'bot_id', 'created_at', 'method', 'content_type', 'telegram_http_status', 'telegram_ok', 'duration_ms', 'error'])
            ->where('user_id', $user->id)
            ->whereHas('bot', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);

                if ($this->bot !== '') {
                    $query->where('public_id', $this->bot);
                }
            })
            ->with('bot:id,public_id,name')
            ->when($this->status === 'success', fn (Builder $query): Builder => $query->where('telegram_ok', true))
            ->when($this->status === 'error', fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query->where('telegram_ok', false)->orWhereNull('telegram_ok')
            ))
            ->when($this->status === 'no_response', fn (Builder $query): Builder => $query->whereNull('telegram_ok'));
    }

    protected function sortableColumns(): array
    {
        return ['created_at', 'duration_ms'];
    }

    public function statusOptions(): array
    {
        return ['success' => 'Успешно', 'error' => 'Ошибка', 'no_response' => 'Нет ответа'];
    }

    public function render(): View
    {
        return view('livewire.dashboard.api-logs', ['records' => $this->records(), 'bots' => $this->bots()])
            ->layout('layouts.app', ['title' => 'API-логи — BotGate']);
    }
}
