@php
    $event = session()->pull('metrika_goal');
    $goal = is_array($event) && is_string($event['name'] ?? null)
        ? \App\Enums\AnalyticsGoal::tryFrom($event['name']) : null;
    $declined = $goal && (app(\App\Services\AnalyticsConsent::class)->read(request())['choice'] ?? null) === 'declined';
@endphp
@if($goal && !$declined && is_string($event['id'] ?? null) && \Illuminate\Support\Str::isUuid($event['id']) && request()->routeIs($goal->route()))
<div hidden data-metrika-goal
     data-event-id="{{ $event['id'] }}"
     data-goal="{{ $goal->value }}"
     data-path="{{ $goal->analyticsPath() }}"
     data-metrika-id="{{ app()->isProduction() ? config('botgate.yandex_metrika_id') : '' }}"
     data-consent-endpoint="{{ route('cookie-consent.show', absolute: false) }}"
     data-version="{{ config('cookie-consent.version') }}"></div>
@endif
