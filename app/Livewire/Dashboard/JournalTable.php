<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Bot;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** @template TRecord of Model */
abstract class JournalTable extends Component
{
    use WithPagination;

    private const int PER_PAGE = 10;

    #[Url]
    public string $bot = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url]
    public string $sort = 'created_at';

    #[Url]
    public string $direction = 'desc';

    /** @return Builder<TRecord> */
    abstract protected function recordsQuery(): Builder;

    /** @return list<string> */
    abstract protected function sortableColumns(): array;

    /** @return array<string, string> */
    abstract public function statusOptions(): array;

    public function updated(string $property): void
    {
        if (in_array($property, ['bot', 'status', 'dateFrom', 'dateTo', 'sort', 'direction'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        abort_unless(in_array($column, $this->sortableColumns(), true), 422);

        $this->direction = $this->sort === $column && $this->direction === 'desc' ? 'asc' : 'desc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('bot', 'status', 'dateFrom', 'dateTo', 'sort', 'direction');
        $this->resetValidation();
        $this->resetPage();
    }

    protected function currentUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        abort_unless($user->hasVerifiedEmail(), 403);

        return $user;
    }

    /** @return Collection<int, Bot> */
    protected function bots(): Collection
    {
        return $this->currentUser()->bots()->orderBy('name')->get(['id', 'public_id', 'name']);
    }

    /** @return LengthAwarePaginator<int, TRecord> */
    protected function records(): LengthAwarePaginator
    {
        try {
            $this->validate([
                'bot' => ['string', 'max:100'],
                'status' => ['string', Rule::in(['', ...array_keys($this->statusOptions())])],
                'dateFrom' => ['nullable', 'date_format:Y-m-d'],
                'dateTo' => ['nullable', 'date_format:Y-m-d', ...($this->dateFrom !== '' ? ['after_or_equal:dateFrom'] : [])],
                'sort' => ['required', Rule::in($this->sortableColumns())],
                'direction' => ['required', Rule::in(['asc', 'desc'])],
            ], [
                'dateTo.after_or_equal' => 'Конец периода должен быть не раньше его начала.',
            ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return new LengthAwarePaginator([], 0, self::PER_PAGE);
        }

        $query = $this->recordsQuery();

        if ($this->dateFrom !== '') {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }

        if ($this->dateTo !== '') {
            $query->where('created_at', '<', Carbon::parse($this->dateTo)->addDay()->startOfDay());
        }

        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query->orderBy($this->sort, $direction)->orderBy('id', $direction);
        $records = $query->paginate(self::PER_PAGE);

        if ($records->currentPage() > $records->lastPage()) {
            $this->resetPage();
            $records = $query->paginate(self::PER_PAGE);
        }

        return $records;
    }
}
