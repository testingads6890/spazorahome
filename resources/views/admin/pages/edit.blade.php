@extends('layouts.admin')

@section('title', 'Edit Page')

@section('content')
@include('admin.partials.simple-form', [
    'title' => 'Edit Page',
    'action' => route('admin.pages.update', $page),
    'back' => route('admin.pages.index'),
    'fields' => [['name'=>'title','label'=>'Title','required'=>true],['name'=>'slug','label'=>'Slug','required'=>true],['name'=>'content','label'=>'Content','type'=>'textarea'],['name'=>'meta_title','label'=>'Meta title'],['name'=>'meta_description','label'=>'Meta description','type'=>'textarea'],['name'=>'is_active','label'=>'Active','type'=>'checkbox','default'=>true]],
    'model' => $page,
])
@endsection
