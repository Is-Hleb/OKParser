@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-6">
                <h2>Пользователи: </h2>
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col col-1">#</th>
                        <th scope="col col-2">Табличка</th>
                        <th scope="col col-2">Статус</th>
                        <th scope="col col-2">Регион</th>
                        <th scope="col col-2">Пользователей спарсилось</th>
                        <th scope="col col-2">собрать</th>
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
                            <th>{{ $info['users_count'] ?? "" }}</th>
                            @if(! \App\Models\Task::where('task_id', "node_{$info['task_id']}")->get())
                                <th class="btn-group">
                                    <a href="{{ route('job.users-friends-subscribers.set-task', ['friends', $info['task_id']]) }}"
                                       class="btn btn-success border-end me-1">друзей</a>
                                    <a href="{{ route('job.users-friends-subscribers.set-task', ['subscribers', $info['task_id']]) }}"
                                       class="btn btn-success">подписчиков</a>
                                </th>
                            @else
                                <th>Задача уже в очереди</th>
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
                        <th scope="col col-2">Пользователей спарсилось</th>
                        <th scope="col col-2">собрать</th>
                    </tr>
                    </thead>
                    <tbody>
{{--                    @foreach($tasks as $task)--}}
{{--                        <tr>--}}
{{--                            @php($jobInfo = $task->jobInfo))--}}

{{--                        </tr>--}}
{{--                    @endforeach--}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
