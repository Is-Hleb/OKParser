<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ParserTaskResource;
use App\Models\JobInfo;
use App\Models\Parser;
use App\Models\ParserTask;
use App\Models\Task;
use Illuminate\Http\Request;

class ParserController extends Controller
{
    public function getTask($token)
    {
        $parser = Parser::findByToken($token);
        $types = $parser->types()->pluck('parser_type_id');

        $task = ParserTask::whereIn("type_id", $types)->where('parser_id', null)->first();

        if($task) {
            $task->parser_id = $parser->id;
            $task->save();
            return new ParserTaskResource($task);
        }
        return null;
    }

    public function taskRunning(Request $request) {
        $valid = $request->validate([
            'id' => 'required|exists:parser_tasks',
            'table_name' => 'required|string',
            'columns' => 'required'
        ]);

        $task = ParserTask::find($valid['id']);

        if($task->status == JobInfo::WAITING) {
            $task->table_name = $valid['table_name'];
            $task->columns = $valid['columns'];
            $task->status = "running";
            $task->save();
            return "ok";
        } else {
            return "Task already updated";
        }
    }

    public function allParserTasks($token) {
        $parser = Parser::findByToken($token);

        return ParserTaskResource::collection($parser->tasks()->whereIn('status', [JobInfo::WAITING, JobInfo::RUNNING])->get());
    }

    public function finishTask($id) {
        $task = ParserTask::find($id);
        $task->status = JobInfo::FINISHED;

        return true;
    }
}
