<?php

namespace App\Jobs;

use App\Models\Job;
use App\Models\JobOutput;
use App\Services\OKApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OkParserApi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $signature;
    private string $method;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $action, array $signature, public OKApi $service)
    {
        $this->signature = $signature;
        $this->method = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $output = JobOutput::create(['job_id' => $this->job->getJobId()]);

        $method = $this->method;
        $result = $this->service->$method(...$this->signature);

        $output->result = $result;

        $output->save();
    }
}
