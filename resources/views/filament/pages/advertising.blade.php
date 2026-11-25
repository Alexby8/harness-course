<x-filament-panels::page>
    {{-- Keep the CSS namespace neutral: cosmetic ad-blocking rules also run in the admin panel. --}}
    <style>
        .bg-metrics-report{--bg-metrics-line:#e5e7eb;--bg-metrics-muted:#64748b;--bg-metrics-panel:#fff;display:grid;gap:24px}
        .dark .bg-metrics-report{--bg-metrics-line:#374151;--bg-metrics-muted:#9ca3af;--bg-metrics-panel:#111827}
        .bg-metrics-report .bg-metrics-muted{color:var(--bg-metrics-muted);font-size:13px;line-height:1.6}
        .bg-metrics-report .bg-metrics-filters{display:flex;flex-wrap:wrap;gap:16px;align-items:end}
        .bg-metrics-report label{display:grid;gap:6px;font-size:13px;flex:1;min-width:160px}
        .bg-metrics-report select,.bg-metrics-report input{border:1px solid var(--bg-metrics-line);border-radius:8px;padding:9px 12px;background:var(--bg-metrics-panel);color:inherit;width:100%}
        .bg-metrics-report .bg-metrics-cards{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}
        .bg-metrics-report .bg-metrics-card{padding:18px;border:1px solid var(--bg-metrics-line);border-radius:12px;background:var(--bg-metrics-panel)}
        .bg-metrics-report .bg-metrics-card>.bg-metrics-muted:first-child{min-height:42px}
        .bg-metrics-report .bg-metrics-number{font-size:28px;font-weight:700;font-variant-numeric:tabular-nums;margin:8px 0}
        .bg-metrics-report .bg-metrics-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
        .bg-metrics-report h2{font-size:17px;font-weight:600;margin-bottom:12px}
        .bg-metrics-report .bg-metrics-box{border:1px solid var(--bg-metrics-line);border-radius:12px;padding:20px;background:var(--bg-metrics-panel);min-width:0}
        .bg-metrics-report .bg-metrics-scroll{overflow-x:auto}
        .bg-metrics-report table{border-collapse:collapse;width:100%;font-size:13px;text-align:left}
        .bg-metrics-report th{color:var(--bg-metrics-muted);font-weight:500;white-space:nowrap}
        .bg-metrics-report th,.bg-metrics-report td{padding:12px 10px;border-bottom:1px solid var(--bg-metrics-line);vertical-align:top}
        .bg-metrics-report td:first-child{max-width:260px;overflow-wrap:anywhere}
        .bg-metrics-report .bg-metrics-link{color:#d97706;text-decoration:underline;text-underline-offset:3px;text-align:left;cursor:pointer}
        .bg-metrics-report .bg-metrics-track{height:12px;background:var(--bg-metrics-line);border-radius:4px;overflow:hidden;display:flex;margin-top:6px}
        .bg-metrics-report .bg-metrics-bar{height:100%;background:#f59e0b;display:block}
        .bg-metrics-report .bg-metrics-other{background:#6366f1}.bg-metrics-report .bg-metrics-unknown{background:#94a3b8}
        .bg-metrics-report .bg-metrics-chart-row{margin:13px 0}
        .bg-metrics-report .bg-metrics-chart-label{display:flex;justify-content:space-between;gap:12px;font-size:13px}
        .bg-metrics-report .bg-metrics-notes{line-height:1.7}.bg-metrics-report .bg-metrics-notes p+p{margin-top:8px}
        .bg-metrics-report .bg-metrics-legend{display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--bg-metrics-muted);margin:8px 0}
        .bg-metrics-report .bg-metrics-dot{display:inline-block;width:9px;height:9px;border-radius:2px;margin-right:5px;background:#f59e0b}
        .bg-metrics-report .bg-metrics-dot.bg-metrics-other{background:#6366f1}.bg-metrics-report .bg-metrics-dot.bg-metrics-unknown{background:#94a3b8}
        @media(max-width:1100px){.bg-metrics-report .bg-metrics-cards{grid-template-columns:repeat(3,minmax(0,1fr))}.bg-metrics-report .bg-metrics-grid{grid-template-columns:1fr}}
        @media(max-width:600px){.bg-metrics-report .bg-metrics-cards{grid-template-columns:1fr 1fr}.bg-metrics-report .bg-metrics-box{padding:14px}}
    </style>
    <div class="bg-metrics-report" wire:loading.attr="aria-busy">
        <div>
            <p>От рекламного перехода до использования BotGate</p>
            <p class="bg-metrics-muted">Период выбирает пользователей по дате регистрации. Их дальнейшая работа учитывается до {{ $report->end->format('d.m.Y H:i') }} ({{ config('app.timezone') }}). Администраторы исключены.</p>
        </div>

        <div class="bg-metrics-filters">
            <label>Зарегистрировались за
                <select wire:model.live="period" aria-label="Период регистрации">
                    <option value="7">7 дней</option><option value="30">30 дней</option><option value="90">90 дней</option><option value="180">180 дней</option>
                </select>
            </label>
            <label>Источник
                <select wire:model.live="channel" aria-label="Источник">
                    <option value="paid">Вся реклама</option>
                    @foreach (\App\Enums\AcquisitionChannel::cases() as $channel)
                        <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                    @endforeach
                    <option value="all">Все источники</option>
                </select>
            </label>
            <label>Кампания
                <select wire:model.live="campaign" aria-label="Кампания">
                    <option value="">Все кампании</option>
                    @foreach ($campaigns as $item)
                        <option value="{{ $item['source']->key() }}">{{ $item['source']->source ?: $item['source']->channel->label() }} · {{ $item['source']->campaign ?: 'Без метки кампании' }}{{ $item['source']->medium !== '' ? ' · '.$item['source']->medium : '' }}</option>
                    @endforeach
                </select>
            </label>
            <x-filament::button color="gray" wire:click="$refresh">Обновить</x-filament::button>
        </div>

        <div class="bg-metrics-cards">
            @foreach (['registered' => 'Зарегистрировались', 'verified' => 'Подтвердили почту', 'bots' => 'Добавили бота', 'used' => 'Начали пользоваться', 'active' => 'Работали за 7 дней'] as $key => $label)
                <div class="bg-metrics-card">
                    <div class="bg-metrics-muted">{{ $label }}</div><div class="bg-metrics-number">{{ $totals[$key] }}</div>
                    @if ($key === 'used' || $key === 'active')
                        <div class="bg-metrics-muted">Неполные данные: {{ $totals[$key === 'used' ? 'usage_unknown' : 'active_unknown'] }}</div>
                    @else
                        <div class="bg-metrics-muted">Уникальные пользователи</div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="bg-metrics-grid">
            <section class="bg-metrics-box" aria-label="Воронка пользователей">
                <h2>Воронка выбранных пользователей</h2>
                @foreach (['registered' => 'Регистрация', 'verified' => 'Подтверждение почты', 'bots' => 'Добавление бота', 'used' => 'Использование'] as $key => $label)
                    @php($percent = $totals['registered'] > 0 ? round($totals[$key] * 100 / $totals['registered'], 1) : 0)
                    <div class="bg-metrics-chart-row">
                        <div class="bg-metrics-chart-label"><span>{{ $label }}</span><strong>{{ $totals[$key] }} · {{ $percent }}%</strong></div>
                        <div class="bg-metrics-track" aria-hidden="true"><span class="bg-metrics-bar" style="width:{{ $percent }}%"></span></div>
                    </div>
                @endforeach
                @php($knownEligible = $totals['eligible'] - $totals['retention_unknown'])
                <p style="margin-top:20px"><strong>Продолжили на 8–14-й день: {{ $totals['retained'] }}</strong></p>
                <p class="bg-metrics-muted">{{ $knownEligible > 0 ? round($totals['retained'] * 100 / $knownEligible, 1).'%' : 'Пока нет оценки' }} · завершили наблюдение и имеют полные данные: {{ $knownEligible }}.</p>
                <p class="bg-metrics-muted">Срок не прошёл: {{ $totals['pending'] }}. Неполные данные за вторую неделю: {{ $totals['retention_unknown'] }}.</p>
                @if ($totals['registered'] === 0)<p class="bg-metrics-muted">В этой выборке пока нет пользователей.</p>@endif
            </section>

            <section class="bg-metrics-box" aria-label="Регистрации по неделям">
                <h2>Все регистрации по неделям</h2>
                <p class="bg-metrics-muted">Все источники за выбранный период, включая неполные недели.</p>
                <div class="bg-metrics-legend"><span><i class="bg-metrics-dot"></i>Реклама</span><span><i class="bg-metrics-dot bg-metrics-other"></i>Другие</span><span><i class="bg-metrics-dot bg-metrics-unknown"></i>Неизвестно</span></div>
                @php($maxWeek = max(1, max(array_column($weeks, 'total'))))
                @foreach ($weeks as $week)
                    <div class="bg-metrics-chart-row">
                        <div class="bg-metrics-chart-label"><span>{{ $week['label'] }}</span><span>{{ $week['total'] }} <span class="bg-metrics-muted">({{ $week['paid'] }} / {{ $week['other'] }} / {{ $week['unknown'] }})</span></span></div>
                        <div class="bg-metrics-track" aria-hidden="true">
                            @foreach (['paid' => '', 'other' => 'bg-metrics-other', 'unknown' => 'bg-metrics-unknown'] as $key => $class)
                                <span class="bg-metrics-bar {{ $class }}" style="width:{{ round($week[$key] * 100 / $maxWeek, 2) }}%"></span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        </div>

        <section class="bg-metrics-box">
            <h2>Сравнение источников</h2><p class="bg-metrics-muted">Все регистрации за период. Нажмите на источник, чтобы открыть его пользователей.</p>
            <div class="bg-metrics-scroll"><table>
                <thead><tr><th>Источник</th><th>Регистрации</th><th>Подтвердили почту</th><th>Добавили бота</th><th>Используют / неизвестно</th><th>Продолжили / оценены</th></tr></thead>
                <tbody>@foreach ($channels as $item)
                    <tr><td><button type="button" class="bg-metrics-link" wire:click="$set('channel', '{{ $item['channel']->value }}')">{{ $item['channel']->label() }}</button></td>
                        <td>{{ $item['totals']['registered'] }}</td><td>{{ $item['totals']['verified'] }}</td><td>{{ $item['totals']['bots'] }}</td>
                        <td>{{ $item['totals']['used'] }} / {{ $item['totals']['usage_unknown'] }}</td>
                        <td>{{ $item['totals']['retained'] }} / {{ $item['totals']['eligible'] - $item['totals']['retention_unknown'] }}</td></tr>
                @endforeach</tbody>
            </table></div>
        </section>

        <section class="bg-metrics-box">
            <h2>Кампании выбранного источника</h2>
            <div class="bg-metrics-scroll"><table>
                <thead><tr><th>Кампания</th><th>Источник / канал</th><th>Регистрации</th><th>Подтвердили почту</th><th>Добавили бота</th><th>Используют / неизвестно</th><th>Продолжили / оценены</th><th>Расходы</th></tr></thead>
                <tbody>@forelse ($campaigns as $item)
                    <tr wire:key="campaign-{{ $item['source']->key() }}">
                        @php($managed = $managedCampaigns->get($item['source']->key()))
                        <td><button type="button" class="bg-metrics-link" wire:click="selectCampaign('{{ $item['source']->key() }}')">{{ $managed?->name ?? ($item['source']->campaign ?: 'Без метки кампании') }}</button>
                            @if ($managed)<div class="bg-metrics-muted">{{ $item['source']->campaign }}</div>@endif
                        </td>
                        <td>{{ $item['source']->source ?: $item['source']->channel->label() }}<div class="bg-metrics-muted">{{ $item['source']->medium }}</div></td>
                        <td>{{ $item['totals']['registered'] }}</td><td>{{ $item['totals']['verified'] }}</td><td>{{ $item['totals']['bots'] }}</td>
                        <td>{{ $item['totals']['used'] }} / {{ $item['totals']['usage_unknown'] }}</td>
                        <td>{{ $item['totals']['retained'] }} / {{ $item['totals']['eligible'] - $item['totals']['retention_unknown'] }}</td>
                        <td>
                            @if ($managed)
                                <a class="bg-metrics-link" href="{{ \App\Filament\Resources\AdvertisingCampaignResource::getUrl('edit', ['record' => $managed]) }}">Открыть</a>
                            @elseif ($item['source']->channel->isAdvertising() && $item['source']->campaign !== '')
                                <a class="bg-metrics-link" href="{{ \App\Filament\Resources\AdvertisingCampaignResource::getUrl('create', ['source_key' => $item['source']->key()]) }}">Добавить</a>
                            @else
                                <span class="bg-metrics-muted">Нет привязки</span>
                            @endif
                        </td>
                    </tr>
                @empty<tr><td colspan="8">За выбранный период регистраций нет.</td></tr>@endforelse</tbody>
            </table></div>
        </section>

        <section class="bg-metrics-box">
            <h2>Пользователи выбранной кампании и источника · {{ $users->total() }}</h2>
            <div class="bg-metrics-filters" style="margin-bottom:16px">
                <label>Найти пользователя<input type="search" wire:model.live.debounce.300ms="search" maxlength="100" placeholder="Email" aria-label="Найти пользователя"></label>
                <label>Этап
                    <select wire:model.live="stage" aria-label="Этап">
                        <option value="all">Все</option><option value="verified">Подтвердили почту</option><option value="bot">Добавили бота</option><option value="used">Начали пользоваться</option><option value="active">Работали за 7 дней</option><option value="retained">Продолжили на 8–14-й день</option><option value="pending">Срок наблюдения не прошёл</option><option value="unknown">Использование: неполные данные</option>
                    </select>
                </label>
            </div>
            <div class="bg-metrics-scroll"><table>
                <thead><tr><th>Пользователь</th><th>Регистрация</th><th>Источник / кампания</th><th>Почта</th><th>Боты</th><th>Использование</th><th>За 7 дней</th><th>На 8–14-й день</th></tr></thead>
                <tbody>@forelse ($users as $user)
                    <tr wire:key="advertising-user-{{ $user->id }}">
                        <td><a class="bg-metrics-link" href="{{ \App\Filament\Resources\UserResource::getUrl('view', ['record' => $user->id]) }}">{{ $user->email }}</a></td>
                        <td style="white-space:nowrap">{{ $user->registeredAt->format('d.m.Y H:i') }}</td>
                        <td>{{ $user->source->channel->label() }}<div class="bg-metrics-muted">{{ $user->source->campaign ?: 'Без метки кампании' }}</div></td>
                        <td>{{ $user->verified ? 'Подтверждена' : 'Не подтверждена' }}</td><td>{{ $user->bots }}</td>
                        <td>{{ $user->usageLabel($user->used) }}</td><td>{{ $user->usageLabel($user->active) }}</td><td>{{ $user->retentionLabel() }}</td>
                    </tr>
                @empty<tr><td colspan="8">Пользователей с такими условиями нет.</td></tr>@endforelse</tbody>
            </table></div>
            <div style="margin-top:16px"><x-filament::pagination :paginator="$users" /></div>
        </section>

        <section class="bg-metrics-box bg-metrics-notes bg-metrics-muted">
            <h2 style="color:inherit">Как читать отчёт</h2>
            <p>Источник определяется по последнему сохранённому переходу с метками перед регистрацией, а при его отсутствии — по первому переходу. Яндекс.Директ определяется по yclid или сочетанию источника Яндекса с рекламным каналом. Без меток и известного referrer источник остаётся неизвестным: это не доказательство органического перехода.</p>
            <p>Использование — успешный API-запрос, кроме getMe и getWebhookInfo, либо успешная доставка webhook клиенту. Входящее событие Telegram, включённый бот и ошибочный запрос сами по себе не считаются использованием. Удалённые и отключённые боты сохраняют вклад в историю.</p>
            <p>Вторая неделя — календарные дни 8–14, где день регистрации считается первым. Даты рассчитываются в часовом поясе {{ config('app.timezone') }}. Оценка появляется после завершения всех 14 дней. Доля продолживших считается среди всех зарегистрировавшихся с завершённым наблюдением и известным результатом.</p>
            <p>Для старых дней успешные API-запросы не разделялись на рабочие и проверочные. Если использование нельзя подтвердить или исключить, показаны неполные данные. Отказ от аналитики удаляет сохранённую привязку источника; исторические итоги могут меняться. Цифры могут отличаться от Метрики из-за браузерных пропусков и другой модели атрибуции.</p>
            <p>Расходы вносятся вручную через «Кампании и расходы». В карточке кампании результаты сопоставляются с расходом по дате сохранённого рекламного перехода. Период этого отчёта отбирает регистрации, поэтому его итоги могут отличаться от результатов отдельной записи расхода.</p>
        </section>
    </div>
</x-filament-panels::page>
