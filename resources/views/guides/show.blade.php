@extends('guides.article')

@section('intro'){!! $introHtml !!}@endsection

@section('article_body')
{!! $preambleHtml !!}
@foreach($bodySections as $section)
    <section id="{{ $section['id'] }}">{!! $section['html'] !!}</section>
@endforeach
@endsection
