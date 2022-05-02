<?php

namespace App\Jobs;

use App\Models\JobInfo;
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
    private OkApi $service;
    private JobInfo $jobInfo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $action, array $signature, JobInfo $jobInfo)
    {
        $this->jobInfo = $jobInfo;
        $this->service = new OKApi();
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
        try {
            $this->jobInfo->status = JobInfo::RUNNING;
            $this->jobInfo->save();

            $method = $this->method;
            $result = $this->service->$method(...$this->signature);

            $this->jobInfo->output = $result;
            $this->jobInfo->status = JobInfo::FINISHED;
            $this->jobInfo->save();
        } catch (\Exception $e) {
            $this->jobInfo->status = JobInfo::FAILED;
            $this->jobInfo->exception = $e->getTrace();
        }
    }
}
