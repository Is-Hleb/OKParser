<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CountryCode;
use App\Models\JobInfo;
use App\Models\Task;
use App\Services\CoreApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UsersByCity extends Controller
{

    private function view()
    {
        return view('web.users-by-cities',[
            'countries' => CountryCode::all(),
            'tasks' => Task::where('type', 8)->get()
        ]);
    }

    public function __invoke(Request $request)
    {
        if($request->isMethod('post')) {

            $cities = $request->input('cities');
            $country = $request->input('country');

            $country = CountryCode::find($country);
            $cities = explode("\r\n", $cities);

            $sig = [
                'country' => $country->name,
                'cities' => $cities,
                'table_name' => 'parser_task_' . $country->code . Str::random(5)
            ];

            $jobInfo = JobInfo::create([
                'status' => JobInfo::WAITING,
                'is_node_task' => true
            ]);

            Task::create([
                'task_id' => 'node_' . Str::random(5),
                'logins' => json_encode($sig),
                'status' => CoreApiService::WAITING,
                'job_info_id' => $jobInfo->id,
                'type' => 8
            ]);
        }
        return $this->view();
    }
}
