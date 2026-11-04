@extends('layouts.panel', ['groups' => \App\Support\PanelNav::vendor(auth()->user()?->vendorAccount(), auth()->user())])

@section('panel_name', 'Seller')
@section('home_url', route('vendor.dashboard'))
