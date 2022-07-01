<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\Http;

class CoreApiService
{
    const RUNNING = 'performing';
    const WAITING = 'in_queue';
    const OK = 'completed';
    const ERROR = 'error';
    const VALIDATION_ERR = 'not_enough_data';

    private string $baseUrl;

    public function __construct(private Task $task)
    {
        $this->baseUrl = config('core_api_service.base_url');
    }

    public function waiting(): void
    {
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::WAITING
        ]);
    }

    public function validationErr(): void
    {
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::VALIDATION_ERR
        ]);
    }

    public function error(): void
    {
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::ERROR
        ]);
    }

    public function running(): void
    {
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::RUNNING
        ]);
    }

    private function ok(): void
    {
        Http::patch("{$this->baseUrl}/task/status", [
            "id" => $this->task->task_id,
            "status" => self::OK
        ]);
    }

    public function data($output, $type): void
    {
        Http::post("{$this->baseUrl}/data", [
            "task_id" => $this->task->task_id,
            "data" => ["data" => $output],
            "type" => $type
        ]);
        $this->ok();
    }
}
