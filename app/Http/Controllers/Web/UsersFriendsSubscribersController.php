<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JobInfo;
use App\Models\Task;
use App\Services\CoreApiService;
use App\Services\ParserDBService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UsersFriendsSubscribersController extends Controller
{

    public function __construct(private ParserDBService $DBService)
    {
    }

    public function setTask($mode, $jobInfoId)
    {
        $type = match ($mode) {
            'friends' => 3,
            "subscribers" => 1
        };

        $jobInfo = JobInfo::find($jobInfoId);
        $newJobInfo = JobInfo::create([
            'status' => JobInfo::WAITING,
            'is_node_task' => true,
            'name' => "{$jobInfo->name}_$mode"
        ]);

        $sig = [
            'users_table' => json_decode($jobInfo->task->logins, true)['table_name'],
            'table_name' => "{$type}_$jobInfoId",
        ];

        $task = Task::create([
            'task_id' => "node_{$jobInfo->id}",
            'logins' => json_encode($sig),
            'status' => CoreApiService::WAITING,
            'job_info_id' => $newJobInfo->id,
            'type' => $type
        ]);
        $newJobInfo->task_id = $task->id;
        $newJobInfo->save();
        return redirect()->back();
    }

    public function __invoke()
    {
        $infos = $this->DBService->getInfos();
        $tasks = [];
        foreach ($infos as $info) {
            $task = Task::where('task_id', "node_{$info['task_id']}")->first();
            if($task) {
                $tasks[] = $task;
            }
        }

        return view('web.users-friends-subscribers', [
            'infos' => $infos,
             'tasks' => $tasks
        ]);
    }
}