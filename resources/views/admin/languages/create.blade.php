@extends('layouts.admin')

@section('title', 'Add Language')

@section('content')
@include('admin.partials.simple-form', [
    'title' => 'Add Language',
    'action' => route('admin.languages.store'),
    'back' => route('admin.languages.index'),
    'fields' => [['name'=>'name','label'=>'Name','required'=>true],['name'=>'code','label'=>'Code (e.g. en)','required'=>true],['name'=>'direction','label'=>'Direction','type'=>'select','options'=>['ltr'=>'LTR','rtl'=>'RTL']],['name'=>'sort_order','label'=>'Sort order','type'=>'number','default'=>0],['name'=>'is_active','label'=>'Active','type'=>'checkbox','default'=>true]],
])
@endsection
