<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdvertisingExpense;
use App\Models\User;

final class AdvertisingExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, AdvertisingExpense $expense): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, AdvertisingExpense $expense): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, AdvertisingExpense $expense): bool
    {
        return (bool) $user->is_admin;
    }

    public function restore(User $user, AdvertisingExpense $expense): bool
    {
        return (bool) $user->is_admin;
    }

    public function forceDelete(User $user, AdvertisingExpense $expense): bool
    {
        return false;
    }
}
