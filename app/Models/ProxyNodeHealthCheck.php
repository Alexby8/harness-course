<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProxyNodeHealthErrorCode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property CarbonImmutable $checked_at */
final class ProxyNodeHealthCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'proxy_node_id',
        'checked_at',
        'successful',
        'latency_ms',
        'version',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'immutable_datetime',
            'successful' => 'boolean',
            'latency_ms' => 'integer',
            'error_code' => ProxyNodeHealthErrorCode::class,
        ];
    }

    /** @return BelongsTo<ProxyNode, $this> */
    public function proxyNode(): BelongsTo
    {
        return $this->belongsTo(ProxyNode::class);
    }
}
