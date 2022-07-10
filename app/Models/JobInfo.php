<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read $id
 * @property int $task_id
 * @property array $output
 * @property string $exception
 * @property Task $task
 * @property boolean $is_node_task
 */
class JobInfo extends Model
{
    public const RUNNING = "running";
    public const WAITING = "waiting";
    public const FINISHED = "finished";
    public const FAILED = "failed";

    use HasFactory;
    protected $fillable = ['status', 'exception', 'output', 'task_id'];
    protected $casts = [
        'output' => 'array'
    ];

    public function task() {
        return $this->belongsTo(Task::class);
    }
}
