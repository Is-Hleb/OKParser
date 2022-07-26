<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $index
 * @property string $name
 */
class ParserType extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $guarded = ['id'];
}
