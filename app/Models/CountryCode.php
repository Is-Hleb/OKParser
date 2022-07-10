<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $name
 * @property int $code
 */
class CountryCode extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public $timestamps = false;
}
