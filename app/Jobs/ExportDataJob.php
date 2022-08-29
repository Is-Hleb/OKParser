<?php

namespace App\Jobs;

use App\Models\ParserTask;
use App\Services\ParserDBService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ExportDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private ParserTask $parserTask)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ParserDBService $DBService)
    {

        switch ($this->parserTask->type->index) {
            case '8':
                $DBService->update($this->parserTask->table_name, 'region', $this->parserTask->name);
                break;
            case '4-avatars':
                $DBService
                    ->getConnection()
                    ->update("
                        update `{$this->parserTask->table_name}`
                        SET avatar = REPLACE(avatar, 'https://ok-bucket.website.yandexcloud.net/', '')
                    ");
                $DBService
                    ->getConnection()
                    ->update("
                        update `{$this->parserTask->table_name}`
                        SET avatar = CONCAT('https://ok-bucket.website.yandexcloud.net/', avatar)
                    ");
                break;
        }

        $DBService->export(
            $this->parserTask->table_name,
            (array)json_decode($this->parserTask->columns, true),
            $this->parserTask->table_name,
            $this->parserTask->id . '_stats'
        );

        $this->parserTask->output_path = $this->parserTask->table_name . '.csv.zip';
        $this->parserTask->save();
    }
}
