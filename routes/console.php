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
    $tasks = BotTask::getWaiting();
    foreach ($tasks as $task) {
        $payload = json_decode($task->answer);
        $id = $payload->id;
        $info = JobInfo::find($id);
        $task->status_task = $info->status;
        $task->save();
    }
});
