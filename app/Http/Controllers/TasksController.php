<?php

namespace App\Http\Controllers;

use App\Jobs\OkParserApi;
use App\Models\JobInfo;
use App\Services\CoreApiService;
use App\Services\OKApi;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Validation\Rule;

class TasksController extends Controller
{
    const TYPES = [
        1 => [
            'name' => 'getUserAuditory',
            'sig' => [
                'mode' => 'subscribers',
            ]
        ],
        3 => [
            'name' => 'getFriendsByApi',
        ],
        4 => [
            '1' => [
                'name' => 'getUserInfo',
            ]
        ]
    ];

    protected CoreApiService $coreApiService;

    public function __invoke(Request $request)
    {
        $request->validate([
            'id' => ['required'],
            'type' => ['required', Rule::in(array_keys(self::TYPES))]
        ]);


        $type = $request->input('type');
        $id = $request->input('id');

        $task = new Task([
            'task_id' => $id,
            'type' => $id
        ]);
        $this->coreApiService = new CoreApiService($task);

        $methods = self::TYPES[$type];
        foreach ($methods as $method) {
            $rules = OKApi::validationRules()[$method['name']];
            $validator = validator($request->all(), $rules);
            if($validator->fails()) {
                $this->coreApiService->validationErr();
            }
        }

        $data = $request->input();
        unset($data['type']);
        unset($data['id']);

        foreach ($methods as $method) {
            $signature = [];
            if(isset($method['sig'])) {
                $signature = $method['sig'];
            }
            $signature = array_merge($signature, $data);

            $jobInfo = new JobInfo([
                'status' => JobInfo::WAITING
            ]);
            $jobInfo->save();

            dispatch((new OkParserApi($method['name'], $signature, $jobInfo, $this->coreApiService)));
        }

        $task->save();
    }
}
