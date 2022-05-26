<?php

namespace App\Console;

use App\Models\BotTask;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\JobInfo;
use App\Jobs\OkParserApi;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $tasks = BotTask::getQeuueTasks();
            foreach ($tasks as $task) {
                $request = $task->dataT;
                $request = json_decode($request, JSON_OBJECT_AS_ARRAY);

                $data = array_filter($request, function ($key) {
                    return $key !== 'job' && $key != 'action';
                }, ARRAY_FILTER_USE_KEY);

                $jobInfo = new JobInfo([
                    'status' => JobInfo::WAITING
                ]);
                $jobInfo->save();

                dispatch((new OkParserApi($request['action'], $data, $jobInfo)));
    
                $jobInfo = JobInfo::find($jobInfo->id);

                $task->status_task = $jobInfo->status;
                info("Set answer");
                $task->answer = json_encode([
                    'job' => 'get',
                    'id' => $jobInfo->id
                ]);
                $task->save();
            }
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
