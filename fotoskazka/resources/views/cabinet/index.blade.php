@extends('layouts.site')

@section('title', 'Личный кабинет — Фотосказка')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="text-3xl font-bold text-gray-900">Личный кабинет</h1>
    <p class="mt-4 text-lg text-gray-600">Здравствуйте, {{ $user->name }}</p>
</div>
@endsection
