<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $task_id
 * @property string $type
 */
class Task extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = ['id'];
}
