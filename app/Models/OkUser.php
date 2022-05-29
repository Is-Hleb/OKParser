<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $login
 * @property string $password
 * @property string $cookies
 * @property boolean $blocked
 */
class OkUser extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = ['id'];
}
