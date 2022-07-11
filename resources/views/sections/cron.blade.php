<div class="container">
    <div class="row">
        <div class="col-3 overflow-scroll">
            <h3>Задачи</h3>
            @foreach($cronTabs as $tab)
                <div class="p-3 shadow-sm my-2 bg-white">
                    <h5>jobInfo №{{ $tab->jobInfo->id }} - cron №{{ $tab->id }}</h5>
                    <span class="text-primary">{{ $tab->jobinfo->status }}</span>
                </div>
            @endforeach

            <h3 class="mt-4">Группы</h3>
            @foreach($tabsWithGroup as $group => $tabs)
                <div class="p-3 shadow-sm my-2 bg-white">
                    <h5>{{ $group }}</h5>
                    <a class="fs-6" href="{{ route('cron.post.output', ['group', $tabs[0]->id]) }}">Последняя разница</a>
                    <br><a class="fs-6" href="{{ route('cron.post.output', ['lastGroup', $tabs[0]->id]) }}">Последний результат</a>
                </div>
            @endforeach
        </div>
        <div class="col-9">
            <h2 class="mb-3">Поставить задачу</h2>
            <form method="post" action="{{ route('cron.post.links') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label for="csv">Название группы задач</label>
                    <input class="form-control" type="text" placeholder="Писать сюда" name="name" id="name">
                </div>
                <div class="form-group">
                    <label for="csv">CSV файл с сылками</label>
                    <input class="form-control" type="file" name="csv" id="csv">
                </div>
                <div class="form-group mt-2">
                    <input class="form-control bg-dark text-white" type="submit" name="submit" id="submit">
                </div>
            </form>
            <div class="bg-white mt-3 p-3">
                <table class="table mt-3">
                    <thead>
                    <tr>
                        <th scope="col col-1">#</th>
                        <th scope="col col-2">Status</th>
                        <th scope="col col-2">Group name</th>
                        <th scope="col col-2">Download</th>
                        <th scope="col col-2">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cronTabs as $tab)
                        <tr>
                            <th scope="row">{{ $tab->id }}</th>
                            <th>{{ $tab->status }}</th>
                            <th>
                                {{ $tab->name ?? "Имя не задано" }}
                            </th>
                            <th>
                                <a href="{{ route('cron.post.output', ['tab', $tab->id]) }}">Скачать результаты</a>
                                <br><a href="{{ route('cron.post.output', ['last', $tab->id]) }}">Скачать последний
                                    результат</a>
                                <br><a href="{{ route('cron.post.output', ['delta', $tab->id]) }}">Скачать дельту</a>
                                <br><a href="{{ route('cron.post.output', ['exceptions', $tab->id]) }}">Скачать ошибки</a>
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
                {{ $cronTabs->links() }}
            </div>
        </div>
    </div>
</div>
