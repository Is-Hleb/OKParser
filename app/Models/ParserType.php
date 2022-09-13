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

    const SUBSCRIBERS = 1;
    const FRIENDS = 3;
    const GROUPS = 16;
    const DETAIL = 4;
    const POSTS = 2;

    public $timestamps = false;
    protected $guarded = ['id'];
}
