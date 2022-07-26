<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Parser;
use App\Models\ParserTask;
use App\Models\ParserType;
use App\Models\Task;
use App\Services\ParserDBService;
use Illuminate\Http\Request;

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
            'parsers' => Parser::paginate(5),
            'tasks' => ParserTask::orderBy('id', 'desc')->paginate(5)
        ]);
    }

    public function create(Request $request)
    {
        ParserTask::create(
            array_merge($request->input(), [
                'type_id' => ParserType::where('index', $request->input('task_type'))->first()->id
            ])
        );
        return redirect()->back();
    }

}
