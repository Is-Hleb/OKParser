<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ParserTaskService;
use App\Services\CoreApiService;
use Illuminate\Http\Request;

class SetTaskController extends Controller
{
    public function __invoke(Request $request, CoreApiService $apiService)
    {
        $input = $request->validate([
            'id' => ['required'],
            'type' => ['required', 'exists:parser_types,index'],
            'logins' => ['required']
        ]);

        $logins = $request->input('logins');
        if(!ParserTaskService::dispachTask($input['type'], $input['id'], $logins)) {
            $apiService->validationErr();
        }

        return response()->json([
            "task_id" => $input['id']
        ]);
    }
}
