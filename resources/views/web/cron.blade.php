@extends('layouts.app')

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-4">
                @foreach($cronTabs as $tab)
                    <div class="my-2 p-2 bg-light border-bottom shadow-sm">
                        <h3>jobInfo №{{ $tab->jobInfo->id }}</h3>
                        <span class="text-primary">{{ $tab->jobinfo->status }}</span>
                    </div>
                @endforeach
            </div>
            <div class="col-8">
                <h2 class="w-75 border-bottom mb-3">Посты ИРИ (Поставить задачу)</h2>
                <form method="post" action="{{ route('cron.post.links') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="csv">CSV файл с сылками</label>
                        <input class="form-control" type="file" name="csv" id="csv">
                    </div>
                    <div class="form-group mt-2">
                        <input class="form-control bg-dark text-white" type="submit" name="submit" id="submit">
                    </div>
                </form>
                <hr>
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col col-1">#</th>
                        <th scope="col col-2">Status</th>
                        <th scope="col col-2">Add to output</th>
                        <th scope="col col-2">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cronTabs as $tab)
                        <tr>
                            <th scope="row">{{ $tab->id }}</th>
                            <th>{{ $tab->status }}</th>
                            <th>
                                <input class="form-check" type="checkbox" name="download-{{ $tab->id }}"
                                       id="download-{{ $tab->id }}">
                            </th>
                            <th>
                                @if($tab->status !== 'finished')
                                    <form method="post" action="{{ route('cron.stop', $tab->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <input class="btn btn-danger" type="submit" value="stop">
                                    </form>
                                @else
                                    Остановлена
                                @endif
                            </th>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <input class="form-control bg-dark text-white" type="submit" value="Скачать">
            </div>
        </div>
    </div>

@endsection
