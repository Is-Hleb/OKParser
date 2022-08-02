<?php

namespace App\Console\Commands;

use App\Models\ParserTask;
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
        $parserTasks = ParserTask::where('is_asup', 1)->where('status', 'finished')->get();

        foreach ($parserTasks as $task) {
            $columns = $task->columns;

        }

        return 0;
    }
}
