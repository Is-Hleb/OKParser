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
                                    <input checked class="form-check" type="radio" name="country"
                                           value="{{ $country->id }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-9">
                    <h3>Поставить задачу</h3>
                    <div class="form-group mb-3">
                        <input class="form-control bg-white" placeholder="регион" type="text" name="name">
                    </div>
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
        <div class="row">
            <div class="col-3">
                <h3>Инструменты парсера: </h3>
                <div class="p-3 shadow-sm my-2 bg-white">
                    <div class="row">
                        <div class="col-7">
                            <span>Остаток пользователей: {{ $users_count }}</span>
                        </div>
                        <div class="col-5 d-flex justify-content-end">
                            <form action="{{ route('tools.reset.users') }}" method="post" class="align-self-center">
                                @csrf
                                @method("PUT")
                                <input class="rounded btn-dark" type="submit" value="обнулить">
                            </form>
                        </div>
                    </div>
                </div>
                <div class="p-3 shadow-sm my-2 bg-white">
                    <div class="row">
                        <div class="col-7">
                            <span>Остаток прокси: {{ $proxies_count }}</span>
                        </div>
                        <div class="col-5 d-flex justify-content-end">
                            <form action="{{ route('tools.reset.proxies') }}" method="post" class="align-self-center">
                                @csrf
                                @method('PUT')
                                <input class="rounded btn-dark" type="submit" value="обнулить">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-9 bg-white p-3">
                <h3>Внешняя БД: </h3>
                <div class="row">
                    <div class="col-2">
                        <form action="{{ route('job.users-by-cities.update-status') }}" method="post">
                            @csrf
                            <label class="form-label">Страница будет загружаться долго</label>
                            <input class="form-control btn btn-primary" type="submit" value="обновить">
                        </form>
                    </div>
                    <div class="col-10">
                        <table class="table">
                            <thead>
                            <tr>
                                <th scope="col col-1">#</th>
                                <th scope="col col-2">Табличка</th>
                                <th scope="col col-2">Статус</th>
                                <th scope="col col-2">Регион</th>
                                <th scope="col col-2">Ссылок осталось</th>
                                <th scope="col col-2">Пользователей спарсилось</th>
                                <th scope="col col-2">Экспорт</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($infos as $info)
                                <tr>
                                    @php($jobInfo = \App\Models\JobInfo::find($info['task_id']))
                                    <th>{{ $info['task_id'] }}</th>
                                    <th>{{ $info['table_name'] }}</th>
                                    <th>{{ $jobInfo->status ?? "не определён" }}</th>
                                    <th>{{ $info['name'] ?? "" }}</th>
                                    <th>{{ $info['count'] }}</th>
                                    <th>{{ $info['users_count'] ?? "" }}</th>
                                    <th class="btn-group">
                                        <a class="btn btn-dark p-1"
                                           href="{{ route('job.users-by-cities.export', [$info['table_name'], $info['task_id']]) }}">экспорт
                                        </a>
                                        @if($jobInfo)
                                            <a class="btn btn-dark p-1"
                                               href="{{ route('job.users-by-cities.parser_again', [$info['task_id']]) }}">допарс
                                            </a>
                                            <form action="{{ route('job.users-by-cities.delete', $info['task_id']) }}"
                                                  method="post">
                                                @csrf
                                                @method("DELETE")
                                                <input class="btn-danger rounded" type="submit" value="удалить">
                                            </form>
                                        @endif
                                    </th>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
