<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobInfo extends Model
{
    public const RUNNING = "running";
    public const WAITING = "waiting";
    public const FINISHED = "finished";
    public const FAILED = "failed";

    use HasFactory;
    protected $fillable = ['status', 'exception', 'output'];
    protected $casts = [
        'output' => 'array'
    ];
}
