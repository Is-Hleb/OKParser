<?php

namespace App\Services;

use App\Models\JobInfo;
use App\Models\Task;
use Illuminate\Support\Facades\Http;

class CoreApiService
{

    const RUNNING = 'performing';
    const WAITING = 'queue';
    const OK = 'completed';
    const ERROR = 'error';
    const VALIDATION_ERR = 'warning';

    private string $baseUrl;

    public function __construct(private Task $task)
    {
        $this->baseUrl = config('core_api_service.base_url');
    }

    public function addJobInfo(JobInfo $jobInfo)
    {
        $this->task->job_info_id = $jobInfo->id;
        $this->task->save();
    }

    public function waiting(): void
    {
        $status = self::WAITING;
        Http::patch("{$this->baseUrl}/v1/task/{$this->task->task_id}?status=$status");
//        Http::patch("{$this->baseUrl}/task/status", [
//            "id" => $this->task->task_id,
//            "status" => self::WAITING
//        ]);
        $this->task->status = self::WAITING;
        $this->task->save();
    }

    public function validationErr(): void
    {
        $err = self::VALIDATION_ERR;
        Http::patch("{$this->baseUrl}/v1/task/{$this->task->task_id}?status=$err");
//        Http::patch("{$this->baseUrl}/task/status", [
//            "id" => $this->task->task_id,
//            "status" => self::VALIDATION_ERR
//        ]);
        $this->task->status = self::VALIDATION_ERR;
        $this->task->save();
    }

    public function error(\Exception $exception): void
    {
        $err = self::ERROR;
        $data = [
            "status" => $exception->getCode(),
            "message" => $exception->getMessage(),
            "error" => $exception->getFile() . "_" . $exception->getLine()
        ];
        $data = json_encode($data);
        $data = urlencode($data);

        Http::patch("{$this->baseUrl}/task/{$this->task->task_id}?status=$err&errorReason=$data");
//        Http::patch("{$this->baseUrl}/task/status", [
//            "id" => $this->task->task_id,
//            "status" => self::ERROR
//        ]);
        $this->task->status = self::ERROR;
        $this->task->save();
    }

    public function running(): void
    {
        $running = self::RUNNING;
        Http::patch("{$this->baseUrl}/task/{$this->task->task_id}?status=$running");
//        Http::patch("{$this->baseUrl}/task/status", [
//            "id" => $this->task->task_id,
//            "status" => self::RUNNING
//        ]);
        $this->task->status = self::RUNNING;
        $this->task->save();
    }

    private function ok(): void
    {
        $status = self::OK;
        Http::patch("{$this->baseUrl}/task/{$this->task->task_id}?status=$status");
//        Http::patch("{$this->baseUrl}/task/status", [
//            "id" => $this->task->task_id,
//            "status" => self::OK
//        ]);
        $this->task->status = self::OK;
        $this->task->save();
    }

    public function data($output, $type): void
    {
        Http::post("{$this->baseUrl}/task/data", [
            "taskId" => $this->task->task_id,
            "data" => ["data" => $output],
            "type" => $type
        ]);
        $this->ok();
    }
}
