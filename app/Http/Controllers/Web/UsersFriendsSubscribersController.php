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
            "subscribers" => 1,
            "avatars" => "4-avatars"
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

    public function export($task_id)
    {
        ini_set('max_execution_time', 0);
        $task = Task::find($task_id);
        $sig = json_decode($task->logins);

        return response()->download($this->DBService->export(
            $sig->table_name,
            ['owner_id', 'user_id'],
            $task->jobInfo->name
        ));
    }

    public function __invoke()
    {
        $infos = $this->DBService->getInfos();
        $tasks = [];
        foreach ($infos as &$info) {
            $task = Task::where('task_id', "node_{$info['task_id']}")->get();
            $info['jobInfo'] = JobInfo::find($info['task_id']);
            if ($task->count()) {
                $tasks[$info['task_id']] = $task;
                $info['jobInfo'] = JobInfo::find($info['task_id']);
                $info['friendsIsset'] = $task->filter(fn($item, $key) => $item->type == 3)->first();
                $info['subscribersIsset'] = $task->filter(fn($item, $key) => $item->type == 1)->first();
                $info['avatarsIsset'] = $task->filter(fn($item, $key) => $item->type == "4-avatars")->first();
            } else {
                $info['subscribersIsset'] = false;
                $info['friendsIsset'] = false;
                $info['avatarsIsset'] = false;
            }
        }

        $outputTasks = [];
        foreach ($tasks as $taskl2) {
            foreach ($taskl2 as $task) {
                $task->sig = json_decode($task->logins);
                $task->users_count = $this->DBService->getAllRowsCount($task->sig->table_name);
                if(str_contains($task->sig->table_name, '3')) {
                    $task->users_not_parsed = $this->DBService->getRowsCount($task->sig->users_table, 'friends', null);
                } else {
                    $task->users_not_parsed = $this->DBService->getRowsCount($task->sig->users_table, 'subscribers_checked', null);
                }
                $outputTasks[] = $task;
            }
        }

        return view('web.users-friends-subscribers', [
            'infos' => $infos,
            'tasks' => $outputTasks
        ]);
    }
}
