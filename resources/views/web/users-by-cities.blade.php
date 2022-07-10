@extends('layouts.app')

@section('content')
    <div class="container">
        <form action="{{ route('job.users-by-cities') }}" method="post">
            @csrf
            <div class="row">
                <div class="col-3">
                    <h3>Страны</h3>
                    @foreach($countries as $country)
                        <div class="p-3 shadow-sm my-2 bg-white">
                            <div class="row">
                                <div class="col-10">
                                    <span>Название: {{ $country->name }}</span>
                                    <br>
                                    <span>Код: {{ $country->code }}</span>
                                </div>
                                <div class="col-2">
                                    <input checked class="form-check" type="radio" name="country" value="{{ $country->id }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-9">
                    <h3>Поставить задачу</h3>
                    <div class="form-group mb-3">
                        <textarea name="cities" placeholder="Список городов" cols="30" rows="10"
                                  class="form-control bg-white"></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <input type="submit" value="Отправить" class="btn-dark w-100 rounded">
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
