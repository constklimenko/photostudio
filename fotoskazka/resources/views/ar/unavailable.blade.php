@extends('ar.layout')

@section('styles')
    main { min-height: 100%; display: flex; flex-direction: column; justify-content: center; padding: 24px 16px; gap: 16px; }
@endsection

@section('content')
    <main>
        <div class="card">
            <div class="brand">Фотосказка</div>
            <h1>{{ $heading }}</h1>
            <p>{{ $message }}</p>
        </div>
    </main>
@endsection
