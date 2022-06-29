@extends('layouts.app')

@section('content')
    <div id="update">
        @include('sections.cron')
    </div>
    <div class="container mt-5 d-flex w-100 justify-content-start">
        <button class="btn btn-primary" id="update-btn">Обновить</button>
        <div class="spinner-border ms-2 pt-1" style="transition: all; transition-delay: 200ms" id="loading" role="status">
            <span class="sr-only" ></span>
        </div>
    </div>
    @push('before-closed-body')
        <script type="text/javascript" src="{{ asset('js/update-view.js') }}"></script>
    @endpush
@endsection
