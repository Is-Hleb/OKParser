<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ok_user_id
 * @property int $proxy_id
 * @property array $cookies
 */
class ProxyCookie extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'cookies' => 'array'
    ];
}
