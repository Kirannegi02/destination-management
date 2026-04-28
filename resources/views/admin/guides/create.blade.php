@extends('admin.layouts.app')

@section('title', 'Add New Guide')
@section('page-title', 'Add New Guide')

@section('content')
@include('admin.guides._form', [
    'guide'      => null,
    'formAction' => route('admin.guides.store'),
    'formMethod' => 'POST',
    'submitLabel'=> 'Create Guide',
])
@endsection
