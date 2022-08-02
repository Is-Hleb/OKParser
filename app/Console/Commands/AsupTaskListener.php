<?php

namespace App\Console\Commands;

use App\Models\ParserTask;
use App\Models\Task;
use Illuminate\Console\Command;

class AsupTaskListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asup:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $parserTasks = ParserTask::where('is_asup_task', 1)->where('status', 'finished')->get();

        foreach ($parserTasks as $parserTask) {
            $task = Task::fins($parserTask->task_id);
            $columns = json_decode($parserTask->columns);


        }

        return 0;
    }
}
