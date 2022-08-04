<?php

namespace App\Console\Commands;

use App\Models\TaskE;
use App\Models\TelegramMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ExportTaskEFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:e:export';

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

    private function format(TaskE $task) {
        $location = explode("-", $task->location);
        $name = explode(" ", $task->name);
        if($task->is_vk) {
            return [
                $task->ibd ?? '""',
                $task->postUrl ?? '""',
                $task->profileUrl ?? '""',
                '""',
                '""',
                $task->activityType == 'like' ? "ЛАЙК" : "КОММЕНТАРИЙ",
                $task->commentText ?? '""',
                $name[0] ?? '""',
                $name[1] ?? '""',
                $task->gender ?? '""',
                $task->age ?? '""',
                $location[0] ?? '""',
                $location[1] ?? '""',
                $location[2] ?? '""',
                $location[3] ?? '""',
            ];
        } else {
            return [
                $task->ibd,
                '""',
                '""',
                $task->postUrl ?? '""',
                $task->profileUrl ?? '""',
                $task->activityType == 'like' ? "ЛАЙК" : "КОММЕНТАРИЙ",
                $task->commentText ?? '""',
                $name[0] ?? '""',
                $name[1] ?? '""',
                $task->gender ?? '""',
                $task->age ?? '""',
                $location[0] ?? '""',
                $location[1] ?? '""',
                $location[2] ?? '""',
                $location[3] ?? '""',
            ];
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $content = "";
        $fileName = now()->format('Y-m-d') . '.csv';
        foreach (TaskE::whereDate("created_at", Carbon::today())->cursor() as $datum) {
            $content .= implode(',', $this->format($datum)) . "\n";
        }
        Storage::disk('s3-iri')->put($fileName, $content);
        TelegramMessage::create([
           "type" => "file",
           "content" => $content,
           "file_name" => $fileName
        ]);
        return 0;
    }
}
