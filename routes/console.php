<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\BotTask;
use App\Jobs\OkParserApi;
use App\Models\JobInfo;

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

Artisan::command('make:admin', function() {
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

Artisan::command('test', function() {
    $tasks = BotTask::getQeuueTasks();
    foreach($tasks as $task) {
        dump($task->answer);
        $request = $task->dataT;
        $request = json_decode($request, JSON_OBJECT_AS_ARRAY);

        $data = array_filter($request, function ($key) {
            return $key !== 'job' && $key != 'action';
        }, ARRAY_FILTER_USE_KEY);

        $jobInfo = new JobInfo([
            'status' => JobInfo::WAITING
        ]);
        $jobInfo->save();

        // dispatch((new OkParserApi($request['action'], $data, $jobInfo)));
    
        $jobInfo = JobInfo::find($jobInfo->id);

        $task->status_task = $jobInfo->status;
        info("Set answer");
        $task->answer = json_encode([
            'job' => 'get',
            'id' => $jobInfo->id
        ]);
        // $task->save();
    }
});