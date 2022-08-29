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
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::WAITING
        ]);
        $this->task->status = self::WAITING;
        $this->task->save();
    }

    public function validationErr(): void
    {
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::VALIDATION_ERR
        ]);
        $this->task->status = self::VALIDATION_ERR;
        $this->task->save();
    }

    public function error(): void
    {
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::ERROR
        ]);
        $this->task->status = self::ERROR;
        $this->task->save();
    }

    public function running(): void
    {
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::RUNNING
        ]);
        $this->task->status = self::RUNNING;
        $this->task->save();
    }

    public function ok(): void
    {
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::OK
        ]);
        $this->task->status = self::OK;
        $this->task->save();
    }

    public function data($output, $type): void
    {
        Http::post("{$this->baseUrl}/data", [
            "data" => ["data" => $output],
            "type" => $type,
            "task_id" => $this->task->task_id
        ]);
         $this->ok();
    }

    public static function updateStatus(string $id, string $status) : void {
        $baseUrl = config('core_api_service.base_url');
        Http::patch("{$baseUrl}/task/status", [
            "id" => $id,
            "status" => $status
        ]);
    }

}
