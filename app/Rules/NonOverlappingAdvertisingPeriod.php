<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\AdvertisingExpense;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

final readonly class NonOverlappingAdvertisingPeriod implements ValidationRule
{
    public function __construct(private int $campaignId, private mixed $start, private ?int $exceptId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($this->start) || ! is_string($value)
            || Validator::make(['start' => $this->start, 'end' => $value], ['start' => 'date_format:Y-m-d', 'end' => 'date_format:Y-m-d'])->fails()) {
            return;
        }
        if (AdvertisingExpense::overlapping($this->campaignId, $this->start, $value, $this->exceptId)->exists()) {
            $fail('Период пересекается с уже внесённым расходом. Отредактируйте существующую запись или выберите другие даты.');
        }
    }
}
