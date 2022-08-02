<?php

namespace App\Http\Controllers;

use App\Jobs\OkParserApi;
use App\Models\JobInfo;
use App\Models\ParserTask;
use App\Models\ParserType;
use App\Services\CoreApiService;
use App\Services\OKApi;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Validation\Rule;

class TasksController extends Controller
{
    const TYPES = [
        1 => [
            1 => [
                'name' => 'getUserSubscribers',
            ]
        ],
        3 => [
            1 => [
                'name' => 'getFriendsByApi',
            ]
        ],
        5 => [
            1 => [
                'name' => 'getGroupFollowers',
            ]
        ],
        4 => [
            1 => [
                'name' => 'getUserInfo',
            ]
        ],
        8 => [

        ]
    ];

    protected CoreApiService $coreApiService;

    public function __invoke(Request $request)
    {
        $request->validate([
            'id' => ['required'],
            'type' => ['required']
        ]);

        $type = $request->input('type');
        $id = $request->input('id');

        if($type == 8) {
            $logins = json_encode([
                'country' => $request->input('country_code'),
                'cities' => $request->input('logins')
            ]);

            $task =Task::create([
                'task_id' => $id,
                'type' => $type,
                'logins' => $logins
            ]);

            ParserTask::create([
                'table_name' => $id,
                'logins' => $task->logins,
                'type_id' => ParserType::where('index', $type)->first()->id,
                'is_asup_task' => 1,
                'name' => "Парсинг по городам _ " . $id,
                'task_id' => $task->id
            ]);

            return response()->json([
                "task_id" => $task->task_id
            ]);
        }

        $task = new Task([
            'task_id' => $id,
            'type' => $type,
            'logins' => json_encode($request->input('logins'))
        ]);
        $this->coreApiService = new CoreApiService($task);

        $methods = self::TYPES[$type];
        foreach ($methods as $method) {
            $rules = OKApi::validationRules()[$method['name']];
            $validator = validator($request->all(), $rules);
            if ($validator->fails()) {
                $this->coreApiService->validationErr();
            }
        }

        $data = $request->input();
        unset($data['type']);
        unset($data['id']);

        $task->save();

        foreach ($methods as $method) {
            $signature = [];
            if (isset($method['sig'])) {
                $signature = $method['sig'];
            }
            $signature = array_merge($signature, $data);

            $jobInfo = new JobInfo([
                'status' => JobInfo::WAITING,
                'task_id' => $task->id
            ]);
            $jobInfo->save();
            $this->coreApiService->addJobInfo($jobInfo);
            dispatch((new OkParserApi($method['name'], $signature, $jobInfo, true)));
        }



        return response()->json([
            "task_id" => $task->task_id
        ]);
    }
}
