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
                            <?php
                            $taskss = \App\Models\Task::where('task_id', "node_{$info['task_id']}")->get();
                            $friendsTsk = $taskss->filter(fn($item, $key) => $item->type == 3)->first();
                            $subscribersTsk = $taskss->filter(fn($item, $key) => $item->type == 1)->first();
                            ?>
                            <th class="btn-group">
                                <?php if(!$friendsTsk) { ?>

                                <a href="{{ route('job.users-friends-subscribers.set-task', ['friends', $info['task_id']]) }}"
                                   class="btn btn-success border-end me-1">друзей</a>
                                <?php } ?>
                                <?php if(!$subscribersTsk) { ?>

                                <a href="{{ route('job.users-friends-subscribers.set-task', ['subscribers', $info['task_id']]) }}"
                                   class="btn btn-success">подписчиков</a>

                            <?php } ?>
                            @if($taskss->count() == 2)
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
                        <th scope="col col-2">Пользователей спарсилось</th>
                        <th scope="col col-2">собрать</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($tasks as $task)
                        <tr>
                            @php($sig = json_decode($tasks->logins))
                            @php($jobInfo = $task->jobInfo)
                            <th>{{ $jobInfo->id }}</th>
                            <th>{{ $sig->table_name }}</th>
                            <th>{{ $jobInfo->status }}</th>
                            <th>{{ $jobInfo->name }}</th>
                            <th>Скоро будет</th>
                            <th>Скоро будет</th>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
