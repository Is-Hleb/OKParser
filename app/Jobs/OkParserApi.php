<?php

namespace App\Jobs;

use App\Models\JobInfo;
use App\Services\CoreApiService;
use App\Services\OKApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class OkParserApi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $signature;
    private string $method;
    private OkApi $service;
    private JobInfo $jobInfo;
    private CoreApiService|null $coreApiService = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $action, array $signature, JobInfo $jobInfo, CoreApiService|null $coreApiService)
    {
        if($coreApiService) {
            $this->coreApiService = $coreApiService;
        }
        $this->jobInfo = $jobInfo;
        $this->service = new OKApi();
        $this->signature = $signature;
        $this->method = $action;
    }

    public function failed(Throwable $e)
    {
        $this->coreApiService?->error();
        $this->jobInfo->status = JobInfo::FAILED;
        $this->jobInfo->exception = $e->getTrace();
        $this->jobInfo->output = $e->getMessage();
        $this->jobInfo->save();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->coreApiService?->running();
            $this->jobInfo->status = JobInfo::RUNNING;
            $this->jobInfo->save();

            $method = $this->method;
            $result = $this->service->$method(...$this->signature);

            $this->jobInfo->output = $result;
            $this->jobInfo->status = JobInfo::FINISHED;
            $dataType =  OKApi::TASKS_CORE_IDS[$method];
            $this->coreApiService?->data($result, $dataType);

        } catch (\Exception $e) {
            $this->coreApiService?->error();
            $this->jobInfo->status = JobInfo::FAILED;
            $this->jobInfo->exception = $e->getTrace();
            $this->jobInfo->output = $e->getMessage();
        } finally {
            $this->jobInfo->save();
        }
    }
}
