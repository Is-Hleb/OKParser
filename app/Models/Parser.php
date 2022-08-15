<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $name
 * @property string $token
 * @property ParserType $types
 * @property array $tasks
 * @property string $ip
 * @property bool $is_asup_parser
 */
class Parser extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = ['id'];

    public static function findByToken($token) {
        return self::where('token', $token)->first();
    }

    public function tasks() {
        return $this->hasMany(ParserTask::class);
    }

    public function types() {
        return $this->belongsToMany(ParserType::class);
    }
}
