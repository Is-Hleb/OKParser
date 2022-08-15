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
        // Http::patch("{$this->baseUrl}/v1/task/{$this->task->task_id}?status=$status");
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
        $response = Http::patch("{$this->baseUrl}/v1/task/{$this->task->task_id}", [
            'status' => $err
        ]);
        dump($response->body(), $response->status());

//        Http::patch("{$this->baseUrl}/task/status", [
//            "id" => $this->task->task_id,
//            "status" => self::VALIDATION_ERR
//        ]);
        $this->task->status = self::VALIDATION_ERR;
        $this->task->save();
    }

    public function error(\Throwable $exception): void
    {
        $err = self::ERROR;
        $data = [
            "status" => $exception->getCode(),
            "message" => $exception->getMessage(),
            "error" => $exception->getFile() . "_" . $exception->getLine()
        ];

        $response = Http::patch("{$this->baseUrl}/task/{$this->task->task_id}", [
            "errorReason" => $data,
            "status" => $err
        ]);
        dump($response->body(), $response->status());

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
        // Http::patch("{$this->baseUrl}/task/{$this->task->task_id}?status=$running");
        $response = Http::patch("{$this->baseUrl}/task/{$this->task->task_id}", [
            // "id" => $this->task->task_id,
            "status" => self::RUNNING
        ]);
        dump($response->body(), $response->status());
        $this->task->status = self::RUNNING;
        $this->task->save();
    }

    private function ok(): void
    {
        $status = self::OK;
        $response = Http::patch("{$this->baseUrl}/task/{$this->task->task_id}?status=$status");
        dump($response->body(), $response->status());
//        Http::patch("{$this->baseUrl}/task/status", [
//            "id" => $this->task->task_id,
//            "status" => self::OK
//        ]);
        $this->task->status = self::OK;
        $this->task->save();
    }

    public function data($output, $type): void
    {
        $response = Http::post("{$this->baseUrl}/task/data", [
            "taskId" => $this->task->task_id,
            "data" => ["data" => $output],
            "type" => $type
        ]);
        dump($response->body(), $response->status(), $type);
        // $this->ok();
    }

    public static function updateStatus(string $id, string $status) : void {
        $baseUrl = config('core_api_service.base_url');
        Http::patch("{$baseUrl}/task/{$id}?status=$status");
    }

}
