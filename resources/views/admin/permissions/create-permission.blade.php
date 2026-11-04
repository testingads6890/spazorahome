@extends('layouts.admin')

@section('title', 'Add Permission')

@section('content')
@include('admin.partials.simple-form', [
    'title' => 'Add Permission',
    'action' => route('admin.permissions.store-permission'),
    'back' => route('admin.permissions.index'),
    'fields' => [['name'=>'name','label'=>'Permission name','required'=>true]],
])
@endsection
