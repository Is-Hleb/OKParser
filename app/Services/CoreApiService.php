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

    public function __construct(private Task $task)
    {
    }

    public function waiting(): void
    {
        Http::patch("http://84.38.187.209:8080/task/status", [
            "id" => $this->task->task_id,
            "status" => self::WAITING
        ]);
    }

    public function validationErr(): void
    {
        Http::patch("http://84.38.187.209:8080/task/status", [
            "id" => $this->task->task_id,
            "status" => self::VALIDATION_ERR
        ]);
    }

    public function error(): void
    {
        Http::patch("http://84.38.187.209:8080/task/status", [
            "id" => $this->task->task_id,
            "status" => self::ERROR
        ]);
    }

    public function running(): void
    {
        Http::patch("http://84.38.187.209:8080/task/status", [
            "id" => $this->task->task_id,
            "status" => self::RUNNING
        ]);
    }

    private function ok(): void
    {
        Http::patch("http://84.38.187.209:8080/task/status", [
            "id" => $this->task->task_id,
            "status" => self::OK
        ]);
    }

    public function data($output): void
    {
        Http::post("http://84.38.187.209:8080/data", [
            "task_id" => $this->task->task_id,
            "data" => $output
        ]);
        $this->ok();
    }
}
