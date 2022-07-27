@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-4">

                <h2>Добавить парсер</h2>
                <form action="{{ route('parser.ui.parser.create') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="">Название</label>
                        <input placeholder="Будет выводиться вместо Id (Если задано)" type="text" name="name" id=""
                               class="form-control bg-white">
                    </div>
                    <div class="form-group">
                        <label for="">Token</label>
                        <input type="text" name="token" id="" placeholder="Должен быть сгенерирован вами"
                               class="form-control bg-white">
                    </div>
                    <div class="form-group border" style="overflow: scroll; height: 200px">
                        <label>Выбрать тип</label>
                        @foreach($parserTypes as $type)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="type_{{ $type->id }}" id="">
                                <label for="" class="form-check-label">{{ $type->name }}-{{ $type->index }}</label>
                            </div>
                        @endforeach
                    </div>
                    <span>(Скрол)</span>
                    <input class="btn btn-primary w-100 rounded-0" type="submit" value="Сохранить">
                </form>

            </div>
            <div class="col-8">
                <h2>Поставить задачу</h2>
                <form action="{{ route('parser.ui.task.create') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Название (Не обязательно)</label>
                        <input class="form-control bg-white" type="text" name="name">
                    </div>
                    <div class="form-group">
                        <label>Выбрать тип</label>
                        <select name="task_type" id="" class="form-control bg-white">
                            @foreach($parserTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }} - {{$type->index}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Присоединить таблицу (Для последовательных задач на сбор)</label>
                        <select name="selected_table" id="" class="form-control bg-white">
                            @foreach($tables as $table)
                                <option value="{{ $table }}">{{ $table }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mt-2 mb-2">
                        <textarea class="form-control bg-white" name="logins" id="" placeholder="logins"
                                  rows="8"></textarea>
                    </div>
                    <input class="btn btn-primary w-100 rounded-0" type="submit" value="Создать">
                </form>
            </div>
        </div>
        <div class="row">
            <h2>Задачи</h2>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col col-1">#</th>
                    <th scope="col col-2">Название</th>
                    <th scope="col col-2">Табличка</th>
                    <th scope="col col-2">Статус</th>
                    <th scope="col col-2">Парсер</th>
                    <th scope="col col-2">Спаршено</th>
                    <th scope="col col-2">Экспорт</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tasks as $task)
                    <tr>
                        <th>{{ $task->id }}</th>
                        <th>{{ $task->name ?? "Не задано"}}</th>
                        <th>{{ $task->table_name ?? "Пока не задано"}}</th>
                        <th>{{ $task->status }}</th>
                        <th>{{ $task->parser ? ($task->parser->name ?? $task->parser->token) : "Пока не задан" }}</th>
                        <th>{{ $task->rows_count }}</th>
                        <th>
                            @if($task->columns)
                                <a class="link-primary" href="#">скачать</a>
                            @else
                                <span>Парсер ещё не отдал данные</span>
                            @endif
                        </th>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $tasks->links() }}
        </div>
        <div class="row">
            <h2>Парсеры</h2>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col col-1">#</th>
                    <th scope="col col-2">Имя</th>
                    <th scope="col col-2">Токен</th>
                    <th scope="col col-2">Колличество задач</th>
                    <th scope="col col-2">Выбранные типы</th>
                </tr>
                </thead>
                <tbody>
                @foreach($parsers as $parser)
                    <tr>
                        <th>{{ $parser->id }}</th>
                        <th>{{ $parser->name ?? "не задано"}}</th>
                        <th>{{ $parser->token }}</th>
                        <th>{{ \App\Models\ParserTask::where('parser_id', $parser->id)->count() }}</th>
                        <th>{{ implode(',', $parser->types()->pluck('index')->toArray()) }}</th>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
