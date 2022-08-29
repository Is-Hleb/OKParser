@extends('layouts.app')

@push('styles')

    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">

@endpush

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
                                @php($task = \App\Models\ParserTask::where('table_name', $table)->first())
                                <option value="{{ $table }}">{{ $table }} - {{ $task->name ?? "Без имени" }}</option>
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
                    <th></th>
                    <th scope="col">#</th>
                    <th scope="col">Название</th>
                    <th scope="col">Табличка</th>
                    <th scope="col">Статус</th>
                    <th scope="col">Скорость парсинга</th>
                    <th scope="col">Парсер</th>
                    <th scope="col">Спаршено</th>
                    <th scope="col">Экспорт</th>
                    <th scope="col">Посчитать</th>
                    <th scope="col">Скачать</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tasks as $task)
                    <tr class="task-wrap">
                        <th class="task-mask" id="task-mask-{{ $task->id }}"></th>
                        <th>{{ $task->id }}</th>
                        <th>{{ $task->name ?? "Не задано"}}</th>
                        <th>{{ $task->table_name ?? "Пока не задано"}}</th>
                        <th>
                            <span
                                class="rounded-3 p-1 {{ $task->status == 'running' ? 'bg-primary text-white' : ($task->status == 'finished' ? 'bg-success text-white' : 'bg-warning') }}">{{ $task->status }}</span>
                        </th>
                        <th>{{ $task->speed * 60 * 60 }} в час</th>
                        <th>{{ $task->parser ? ($task->parser->name ?? $task->parser->token) : "Пока не задан" }}</th>
                        <th id="count-{{ $task->id }}">{{ $task->rows_count }}</th>
                        <th>
                            @if($task->columns)
                                <a class="link-primary"
                                   href="{{ route('parser.ui.task.export', $task->id) }}">подготовить</a>
                            @else
                                <span>Парсер ещё не отдал данные</span>
                            @endif
                        </th>
                        <th>
                            <input type="checkbox" class="form-check-input check-count" id="check-{{ $task->id }}">
                        </th>
                        <th>
                            @if($task->output_path)
                                <a href="{{ route('parser.ui.task.download', $task->id) }}">скачать</a>
                            @else
                                Сначала подготовь файл
                            @endif
                        </th>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <button id="copy-count" class="btn btn-primary"><span>Сумма:</span> <span id="show-count"></span></button>
        </div>
        <div class="row">
            <h2>Парсеры</h2>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col col-1">#</th>
                    <th scope="col col-2">Имя</th>
                    <th scope="col col-2">Токен</th>
                    <th scope="col col-2">Колличество задач (работают)</th>
                    <th scope="col col-2">Колличество задач (остановлены)</th>
                    <th scope="col col-2">Выбранные типы</th>
                    <th scope="col col-2">ip</th>
                </tr>
                </thead>
                <tbody>
                @foreach($parsers as $parser)
                    <tr>
                        <th>{{ $parser->id }}</th>
                        <th>{{ $parser->name ?? "не задано"}}</th>
                        <th>{{ $parser->token }}</th>
                        <th>{{ \App\Models\ParserTask::where('parser_id', $parser->id)->where('status', \App\Models\JobInfo::RUNNING)->count() }}</th>
                        <th>{{ \App\Models\ParserTask::where('parser_id', $parser->id)->where('status', 'stopped')->count() }}</th>
                        <th>{{ implode(',', $parser->types()->pluck('index')->toArray()) }}</th>
                        <th>{{ $parser->ip }}</th>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('before-closed-body')
    <script>
        var TASKS = {{ \Illuminate\Support\Js::from($tasks) }}
    </script>
    <script type="text/javascript" src="{{ asset('js/export_task.js') }}" defer></script>
    <script type="text/javascript" src="{{ asset('js/check-count.js') }}" defer></script>
@endpush
