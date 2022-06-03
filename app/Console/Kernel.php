<?php

namespace App\Console;

use App\Models\BotTask;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\JobInfo;
use App\Jobs\OkParserApi;
use App\Models\CronTaskinfo;

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
                if (!$request) {
                    continue;
                }
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

        $schedule->call(function () {
            $tasks = BotTask::getWaiting();
            foreach ($tasks as $task) {
                $payload = json_decode($task->answer);
                $id = $payload->id;
                $info = JobInfo::find($id);
                $task->status_task = $info->status;
                $task->save();
            }
        })->everyFiveMinutes();
        
        $schedule->call(function () {
            $cronTasks = CronTaskinfo::where('status', JobInfo::WAITING)->get();
            foreach ($cronTasks as $cronTask) {
                if ($cronTask->jobInfo->status === JobInfo::WAITING) {
                    OkParserApi::dispatch($cronTask->method, $cronTask->signature, $cronTask->jobInfo);
                }
                $jobInfo = $cronTask->jobInfo()->first();
                if ($jobInfo->status === JobInfo::FINISHED) {
                    $jobOutput = $jobInfo->output;
                    $cronOutput = $cronTask->output;
                    if (!$cronTask->output) {
                        $cronTask->output = [
                            now()->format('d.m.y h:m') => $jobOutput
                        ];
                    } else {
                        $cronTask->output = array_merge($cronOutput, [
                            now()->format('d.m.y h:m') => $jobOutput
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
        })->daily();
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
