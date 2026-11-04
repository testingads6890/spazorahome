@extends('layouts.admin')

@section('title', 'Add Page')

@section('content')
@include('admin.partials.simple-form', [
    'title' => 'Add Page',
    'action' => route('admin.pages.store'),
    'back' => route('admin.pages.index'),
    'fields' => [['name'=>'title','label'=>'Title','required'=>true],['name'=>'slug','label'=>'Slug','required'=>true],['name'=>'content','label'=>'Content','type'=>'textarea'],['name'=>'meta_title','label'=>'Meta title'],['name'=>'meta_description','label'=>'Meta description','type'=>'textarea'],['name'=>'is_active','label'=>'Active','type'=>'checkbox','default'=>true]],
])
@endsection
