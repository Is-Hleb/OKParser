@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-6">
                <h2>Пользователи: </h2>
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col col-2">Статус</th>
                        <th scope="col col-2">Регион</th>
                        <th scope="col col-2">спарсилось</th>
                        <th scope="col col-2">собрать</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($infos as $info)
                        <tr>
                            @php($jobInfo = $info['jobInfo'])
                            <th>{{ $jobInfo->status ?? "не определён" }}</th>
                            <th>{{ $info['name'] ?? "" }}</th>
                            <th>{{ $info['users_count'] ?? "" }}</th>

                            <th class="btn-group">
                                @if(!$info['friendsIsset'])

                                    <a href="{{ route('job.users-friends-subscribers.set-task', ['friends', $info['task_id']]) }}"
                                       class="btn btn-success border-end me-1">друзей</a>
                                @endif
                                @if(!$info['subscribersIsset'])

                                    <a href="{{ route('job.users-friends-subscribers.set-task', ['subscribers', $info['task_id']]) }}"
                                       class="btn btn-success">подписчиков</a>

                                @endif
                                @if($info['friendsIsset'] && $info['subscribersIsset'])
                                    Задача уже в очереди
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="col-6">
                <h2>Задачи: </h2>
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col col-1">#</th>
                        <th scope="col col-2">Табличка</th>
                        <th scope="col col-2">Статус</th>
                        <th scope="col col-2">Регион</th>
                        <th scope="col col-2">спарсилось</th>
                        <th scope="col col-2">осталось</th>
                        <th scope="col col-2">скачать</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($tasks as $task)
                        <tr>
                            @php($jobInfo = $task->jobInfo)
                            <th>{{ $jobInfo->id }}</th>
                            <th>{{ $task->sig->table_name }}</th>
                            <th>{{ $jobInfo->status }}</th>
                            <th>{{ $jobInfo->name }}</th>
                            <th>{{ number_format($task->users_count, thousands_separator:'_') }}</th>
                            <th>{{ $task->users_not_parsed }}</th>
                            <th>
                                <a href="{{ route("job.users-friends-subscribers.export", $jobInfo->task_id) }}">скачать</a>
                            </th>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
