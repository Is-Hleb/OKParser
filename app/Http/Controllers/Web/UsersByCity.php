<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CountryCode;
use App\Models\JobInfo;
use App\Models\OkUser;
use App\Models\Proxy;
use App\Models\Task;
use App\Services\CoreApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class UsersByCity extends Controller
{

    private function view($tables = [])
    {
        if (!$tables) {
            $tables = DB::connection('parser')->select('SHOW TABLES');
            $tables = array_map(function ($table) {
                return $table->Tables_in_parser;
            }, $tables);
        }

        $tables = array_filter($tables, fn($table) => str_starts_with($table, "parser_task"));

        $infos = [];
        foreach ($tables as $table) {
            $info = Cache::get($table, false);
            if (!$info) {
                $info = DB::connection('parser')->select('SELECT COUNT(*) FROM search_links WHERE `table` = "' . $table . '"');
                $job_id = explode('_', $table)[2];
                $info = [
                    'task_id' => $job_id,
                    'count' => array_values(get_object_vars($info[0]))[0],
                    'table_name' => $table,
                    'name' => JobInfo::find($job_id)->name ?? "Не задано",
                    'users_count' => get_object_vars(DB::connection('parser')->select("SELECT COUNT(*) FROM `$table`")[0])['COUNT(*)']
                ];
                Cache::put($table, $info);
            }
            $infos[] = $info;
        }
        return view('web.users-by-cities', [
            'countries' => CountryCode::all(),
            'tasks' => Task::where('type', 8)->get(),
            'infos' => $infos,
            'users_count' => OkUser::where('blocked', 0)->count(),
            'proxies_count' => Proxy::where('blocked', 0)->count(),
            'jobs' => JobInfo::where('is_node_task', 1)->get()
        ]);
    }

    public function delete($job_id)
    {
        try {
            $table_name = "parser_task_$job_id";
            DB::connection('parser')->select("DROP TABLE $table_name");
        } catch(\Exception $exception) {

        }

        $task_id = JobInfo::find($job_id)->task_id;
        JobInfo::destroy($job_id);
        Task::destroy($task_id);

        return redirect()->back();
    }

    public function export($table_name, $jobId)
    {
        $jobInfo = JobInfo::find($jobId);
        $keys = ['social_id', 'gender', 'age_range', 'edu', "city", "region", "name", "work"];

        DB::connection('parser')->select("UPDATE $table_name SET name = REPLACE(name, '\n', '')");
        DB::connection('parser')->table($table_name)->update(['region' => $jobInfo->name]);
        $table = DB::connection('parser')->table($table_name)->cursor();

        $table_name = str_replace(' ', '_', $jobInfo->name);
        $csv_file_path = storage_path("$table_name.csv");
        file_put_contents($csv_file_path, implode(",", $keys) . "\n");

        $file = fopen($csv_file_path, 'a');
        foreach ($table as $row) {
            $info = [];
            foreach ($keys as $key) {
                $info[$key] = $row->$key ?? "";
            }
            fwrite($file, implode(",", $info) . "\n");
        }
        fclose($file);

        $zip = new ZipArchive;
        $zip_path = storage_path("$table_name.csv.zip");

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($csv_file_path, "$table_name.csv");
            $zip->close();
            unlink($csv_file_path);
        }

        return response()->download($zip_path);
    }

    public function updateStatus()
    {
        $tables = DB::connection('parser')->select('SHOW TABLES');
        $tables = array_map(function ($table) {
            return $table->Tables_in_parser;
        }, $tables);

        foreach ($tables as $table) {
            Cache::put($table, false);
        }

        return redirect()->back();
    }

    public function __invoke(Request $request)
    {
        if ($request->isMethod('post')) {

            $cities = $request->input('cities');
            $country = $request->input('country');

            $country = CountryCode::find($country);
            $cities = explode("\r\n", $cities);
            $cities = array_map(function($city){
                return trim(str_replace(' ', '', str_replace(" ", '', $city)));
            }, $cities);

            $jobInfo = JobInfo::create([
                'status' => JobInfo::WAITING,
                'is_node_task' => true,
                'name' => $request->input('name')
            ]);

            $sig = [
                'country' => $country->name,
                'cities' => $cities,
                'table_name' => 'parser_task_' . $jobInfo->id
            ];

            $task = Task::create([
                'task_id' => 'node_' . Str::random(5),
                'logins' => json_encode($sig),
                'status' => CoreApiService::WAITING,
                'job_info_id' => $jobInfo->id,
                'type' => 8
            ]);
            $jobInfo->task_id = $task->id;
            $jobInfo->save();
            return redirect()->back();
        } else {
            return $this->view();
        }
    }
}
