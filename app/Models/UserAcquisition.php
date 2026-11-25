<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserAcquisition extends Model
{
    protected $fillable = ['first_touch', 'last_tagged_touch', 'metrika_client_id'];

    protected function casts(): array
    {
        return ['first_touch' => 'array', 'last_tagged_touch' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
