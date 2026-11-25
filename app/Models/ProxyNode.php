<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProxyNodeStatus;
use Database\Factories\ProxyNodeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property ProxyNodeStatus $status */
final class ProxyNode extends Model
{
    /** @use HasFactory<ProxyNodeFactory> */
    use HasFactory;

    protected $fillable = [
        'node_id',
        'name',
        'url',
        'secret_encrypted',
        'priority',
        'enabled',
        'status',
        'last_check_at',
        'last_success_at',
        'last_failure_at',
        'response_time_ms',
        'version',
    ];

    protected $hidden = [
        'secret_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'secret_encrypted' => 'encrypted',
            'priority' => 'integer',
            'enabled' => 'boolean',
            'status' => ProxyNodeStatus::class,
            'last_check_at' => 'immutable_datetime',
            'last_success_at' => 'immutable_datetime',
            'last_failure_at' => 'immutable_datetime',
            'response_time_ms' => 'integer',
        ];
    }

    /**
     * @param  Builder<ProxyNode>  $query
     * @return Builder<ProxyNode>
     */
    public function scopeRoutable(Builder $query): Builder
    {
        return $query
            ->where('enabled', true)
            ->where('status', ProxyNodeStatus::Online->value)
            ->orderBy('priority')
            ->orderBy('id');
    }

    public function isRoutable(): bool
    {
        return $this->enabled && $this->status === ProxyNodeStatus::Online;
    }

    /** @return HasMany<ProxyNodeHealthCheck, $this> */
    public function healthChecks(): HasMany
    {
        return $this->hasMany(ProxyNodeHealthCheck::class);
    }
}
