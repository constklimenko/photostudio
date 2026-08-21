@extends('layouts.site')

@section('title', 'Вход — Фотосказка')

@section('content')
<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="font-heading text-2xl font-normal tracking-wide text-gray-900 text-center">Вход</h1>

    @if ($errors->any())
        <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ $errors->first('email') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
            <input type="password" name="password" id="password" required
                   class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                Запомнить меня
            </label>
        </div>

        <button type="submit"
                class="w-full px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg hover:opacity-90 transition">
            Войти
        </button>
    </form>
</div>
@endsection
