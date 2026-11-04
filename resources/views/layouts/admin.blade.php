@extends('layouts.panel', ['groups' => \App\Support\PanelNav::admin()])

@section('panel_name', 'Admin')
@section('home_url', route('admin.dashboard'))
