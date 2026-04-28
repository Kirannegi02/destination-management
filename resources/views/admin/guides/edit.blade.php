@extends('admin.layouts.app')

@section('title', 'Edit Guide')
@section('page-title', 'Edit Guide')

@section('content')
@include('admin.guides._form', [
    'guide'      => $guide,
    'formAction' => route('admin.guides.update', $guide->id),
    'formMethod' => 'PUT',
    'submitLabel'=> 'Save Changes',
])
@endsection
