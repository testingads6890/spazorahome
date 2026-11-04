@extends('layouts.admin')

@section('title', 'Add Currency')

@section('content')
@include('admin.partials.simple-form', [
    'title' => 'Add Currency',
    'action' => route('admin.currencies.store'),
    'back' => route('admin.currencies.index'),
    'fields' => [['name'=>'name','label'=>'Name','required'=>true],['name'=>'code','label'=>'Code (e.g. INR)','required'=>true],['name'=>'symbol','label'=>'Symbol','required'=>true],['name'=>'exchange_rate','label'=>'Rate: units of this currency per 1 INR (display only)','type'=>'number','step'=>'0.0001','required'=>true],['name'=>'position','label'=>'Symbol position','type'=>'select','options'=>['before'=>'Before amount','after'=>'After amount']],['name'=>'sort_order','label'=>'Sort order','type'=>'number','default'=>0],['name'=>'is_active','label'=>'Active','type'=>'checkbox','default'=>true]],
])
@endsection
