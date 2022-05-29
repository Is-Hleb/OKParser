<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $app_key
 * @property string $key
 * @property string $secret
 */
class ApiToken extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public $timestamps = false;
    
}
