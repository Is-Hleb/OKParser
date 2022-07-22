<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $link
 * @property string $task_key
 * @property array $data
 */
class Link extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'data' => 'array'
    ];
}
