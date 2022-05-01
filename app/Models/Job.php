<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    public function payload() : Attribute {
        return Attribute::make(
          get: function($value) {
              $data = json_decode($value, true);
              return array_filter($data, fn($key) => !in_array($key, [
                  'data', 'job', 'display_name',
              ]), ARRAY_FILTER_USE_KEY);
            }
        );
    }

    public function output() {
        return $this->hasOne(JobOutput::class);
    }
}
