<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ExportDataJob;
use App\Models\CountryCode;
use App\Models\Parser;
use App\Models\ParserTask;
use App\Models\ParserType;
use App\Services\ParserDBService;
use Illuminate\Http\Request;
use App\Services\ParserTaskService;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    public function __invoke(ParserDBService $DBService)
    {
        $tables = $DBService->getTables();
        $parserTables = ParserTask::get()->pluck('table_name')->toArray();
        $tablesToShow = [];

        foreach ($parserTables as $table) {
            if (in_array($table, $tables)) {
                $tablesToShow[] = $table;
            }
        }


        return view('web.set-task', [
            'tables' => $tablesToShow,
            'parserTypes' => ParserType::all(),
            'parsers' => Parser::all(),
            'tasks' => ParserTask::orderBy('id', 'desc')->get()
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->input('task_type');
        $type = ParserType::find($type)->index;
        switch ($type) {
            case ParserTaskService::USER_SUBSCRIBERS:
                ParserTaskService::userSubscribers($request->input('selected_table'), $request->input('name'));
                break;
            case ParserTaskService::USERS_BY_CITIES:
                $logins = $request->input('logins');
                $logins = explode("\r\n", $logins);
                $country = CountryCode::find($logins[0])->name;
                unset($logins[0]);
                $cities = array_map(function ($city) {
                    return rtrim(ltrim(str_replace("&nbsp;", " ", htmlentities($city))));
                }, $logins);

                ParserTaskService::usersByCities($country, $cities, $request->input('name'));
                break;
            case ParserTaskService::USER_FRIENDS:
                ParserTaskService::userFriends($request->input('selected_table'), $request->input('name'));
                break;
            case ParserTaskService::USERS_AVATARS:
                ParserTaskService::userAvatars($request->input('selected_table'), $request->input('name'));
                break;

        }

        return redirect()->back();
    }

    public function export($task_id, ParserDBService $parserDBService)
    {
        ExportDataJob::dispatch(ParserTask::find($task_id));
        return redirect()->back();
//        $task = ParserTask::find($task_id);
//        $file = $parserDBService->export($task->table_name, json_decode($task->columns, true), $task->name ?? "Без_имени");
//        return response()->file($file);
    }

    public function exportStats($task_id)
    {
        $task = ParserTask::find($task_id);
        return Cache::get($task->id . '_stats', 0);
    }

    public function download($task_id) {
        $task = ParserTask::find($task_id);
        return response()->file(storage_path($task->output_path));
    }

}
