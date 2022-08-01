<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ParserTaskResource;
use App\Models\JobInfo;
use App\Models\Parser;
use App\Models\ParserTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ParserController extends Controller
{
    public function getTask($token)
    {
        $parser = Parser::findByToken($token);
        $types = $parser->types()->pluck('parser_type_id');

        $task = ParserTask::whereIn("type_id", $types)->where('parser_id', null)->first();

        if ($task) {
            $task->parser_id = $parser->id;
            $task->save();
            return new ParserTaskResource($task);
        }
        return null;
    }

    public function updateCount(int $taskId, Request $request)
    {
        $task = ParserTask::find($taskId);
        $countBefore = $task->rows_count;
        Cache::put("task-count-$task->id", $task->rows_count);
        $task->rows_count = $request->input("count");
        $task->save();

        $timeDiff = now()->diffInSeconds(Cache::get("count_time-$taskId", now()));
        $iterationCount = $request->input("count") - $countBefore;

        if($timeDiff !== 0) {
            $speed = $iterationCount / $timeDiff;
        } else {
            $speed = 0;
        }

        Cache::put("count_time-$taskId", now());
        Cache::put("speed-$taskId", $speed);

        return "ok";
    }

    public function taskRunning(Request $request)
    {
        $valid = $request->validate([
            'id' => 'required|exists:parser_tasks',
            'table_name' => 'required|string',
            'columns' => 'required'
        ]);

        $task = ParserTask::find($valid['id']);


        $task->table_name = $valid['table_name'];
        $task->columns = $valid['columns'];
        $task->status = "running";
        $task->save();
        return "ok";

    }

    public function allParserTasks($token)
    {
        $parser = Parser::findByToken($token);

        return ParserTaskResource::collection($parser->tasks()->whereIn('status', [JobInfo::WAITING, JobInfo::RUNNING])->get());
    }

    public function finishTask($id)
    {
        $task = ParserTask::find($id);
        $task->status = JobInfo::FINISHED;
        $task->save();
        return true;
    }
}
