@extends('guides.layout')

@section('title', e($article['seo_title'] ?? $article['title']))
@section('description', e($article['description']))
@section('canonical_path', $article['path'])
@section('og_type', 'article')

@section('content')
<nav aria-label="Хлебные крошки" class="pt-8 text-sm text-gray-500">
    <a href="{{ route('guides.index') }}" class="text-gray-400 hover:text-indigo-300 transition">← Все руководства</a>
</nav>
<header class="max-w-4xl pt-8 pb-10 sm:pt-12 sm:pb-12">
    @include('guides.partials.article-meta')
    <h1 class="text-3xl leading-tight font-bold tracking-tight text-white [overflow-wrap:anywhere] sm:text-5xl sm:leading-tight">{{ $article['title'] }}</h1>
    <div class="guide-intro mt-6 max-w-3xl text-lg leading-relaxed text-gray-400">@yield('intro')</div>
</header>
<div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_220px] lg:gap-12">
    <aside class="min-w-0 lg:sticky lg:top-24 lg:order-last" aria-label="Оглавление">
        <div class="hidden border-l border-gray-800 pl-5 lg:block">
            <p class="mb-5 text-xs font-semibold uppercase tracking-widest text-gray-500">В этом руководстве</p>
            @include('guides.partials.contents')
        </div>
        <details class="rounded-xl border border-gray-800 bg-gray-900/40 p-5 lg:hidden">
            <summary class="cursor-pointer text-sm font-medium text-gray-200">Содержание руководства</summary>
            <div class="mt-5">@include('guides.partials.contents')</div>
        </details>
    </aside>
    <article class="guide-prose min-w-0"
             data-guide-view="{{ route('guides.record-view', ['slug' => $guide->slug], absolute: false) }}"
             data-csrf="{{ csrf_token() }}">
        @yield('article_body')
    </article>
</div>
@endsection
