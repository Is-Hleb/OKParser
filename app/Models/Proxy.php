<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $user
 * @property string $password
 * @property string $ip
 */
class Proxy extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
}
