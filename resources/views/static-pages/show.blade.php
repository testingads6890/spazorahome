@extends('static-pages.layout')

{{-- A page written in Admin > Static pages. --}}
@section('title', ($page->meta_title ?: $page->title) . ' - SpazoraHome')
@if($page->meta_description)
    @section('meta_description', $page->meta_description)
@endif
@section('crumb', $page->title)
@section('kicker', 'SpazoraHome')
@section('heading', $page->title)

@section('doc')
    <div class="static-page-content">
        {!! $page->content !!}
    </div>
@endsection
