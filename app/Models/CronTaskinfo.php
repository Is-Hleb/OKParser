<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array $signature
 * @property boolean $status
 * @property string $method
 * @property int $job_info_id
 * @property string $name
 */
class CronTaskinfo extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'signature' => 'array',
        'output' => 'array'
    ];

    public function jobInfo() {
        return $this->belongsTo(JobInfo::class);
    }
}
