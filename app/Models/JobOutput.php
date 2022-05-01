<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOutput extends Model
{
    use HasFactory;
    protected $fillable = ['job_id', 'result', 'trace'];

    protected $casts = [
        'result' => 'array',
        'trace' => 'array'
    ];

    public function job() {
        return $this->belongsTo('jobs');
    }
}
