<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdvertisingCampaign;
use App\Models\User;

final class AdvertisingCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, AdvertisingCampaign $campaign): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, AdvertisingCampaign $campaign): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, AdvertisingCampaign $campaign): bool
    {
        return false;
    }
}
