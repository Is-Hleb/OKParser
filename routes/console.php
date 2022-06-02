<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\BotTask;
use App\Jobs\OkParserApi;
use App\Models\CronTaskinfo;
use App\Models\JobInfo;
use App\Models\OkUser;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('make:admin', function () {
    User::create([
        'name' => "Vika",
        'password' => Hash::make("U__asd123F"),
        'email' => "vika3834@gmail.com"
    ]);
    User::create([
        'name' => "IsHleb",
        'password' => Hash::make('6QakPah2'),
        'email' => "hlebIshenko@gmail.com"
    ]);
});

Artisan::command('test', function () {
    $cronTasks = CronTaskinfo::where('status', JobInfo::WAITING)->get();
            foreach ($cronTasks as $cronTask) {
                if ($cronTask->jobInfo->status === JobInfo::WAITING) {
                    OkParserApi::dispatch($cronTask->method, $cronTask->signature, $cronTask->jobInfo);
                }
                $jobInfo = $cronTask->jobInfo()->first();
                if ($jobInfo->status === JobInfo::FINISHED) {
                    $jobOutput = $jobInfo->output;
                    $cronOutput = $cronTask->output;
                    $time = now()->format('d.m.y h:m');
                    
                    $array[$time] = $jobOutput;
                    if (!$cronTask->output) {
                        $cronTask->output = $array;
                    } else {
                        $cronTask->output = array_merge($cronOutput, [
                            $time => $jobOutput
                        ]);
                    }
                

                    $jobInfo = new JobInfo([
                    'status' => JobInfo::WAITING
                ]);
                    $jobInfo->save();
                    $cronTask->job_info_id = $jobInfo->id;
                    $cronTask->save();
                }
            }
});


Artisan::command('csv', function () {
    $ids = DB::connection('parser')->select("select social_id from `users` WHERE `city` <> '' LIMIT 1000");
    
    foreach($ids as $id) {
        $idss[] = $id->social_id;
    }
    
    $request = [
        'action' => 'getUserInfo',
        'ids' => $idss
    ];
    
    $data = array_filter($request, function ($key) {
        return $key !== 'job' && $key != 'action';
    }, ARRAY_FILTER_USE_KEY);

    $jobInfo = new JobInfo([
        'status' => JobInfo::WAITING
    ]);
    $jobInfo->save();

    OkParserApi::dispatchSync($request['action'], $data, $jobInfo);

    $jobInfo = JobInfo::find($jobInfo->id);

    $output = $jobInfo->output;
    $content = [];
    foreach($output as $obj) {
        $tmp = [];
        foreach($obj as $key => $value) {
            $tmp[] = $value;
        }
        $content[] = $tmp;
    }

    $fp = fopen('output.csv', 'r+');
    foreach ($content as $item) {
        fputcsv($fp, $item);
    }

    
    dump($output);
});


Artisan::command('token', function() {
    echo OkUser::count();
    
});