<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

class TasksController extends Controller
{
    public function __construct()
    {

    }

    public function register(Request $request) 
    {
        $request->validate([
            'id' => 'required',
            'type' => 'required'
        ]);
        Task::create([ 
            'task_id' => $request->input('id'),
            'type' => $request->input('type')
        ]);

        return \response()->json([
            'task_id' => $request->input('id')
        ]);
    }
}
