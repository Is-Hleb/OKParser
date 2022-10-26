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
    const USER_POSTS = 11;
    const MUSIC_ALBUMS = 17;
    const USER_POST_DETAIL = 2;
    const GROUP_POST_DETAIL = 6;
    const USERS_BY_CITIES = 8;
    const GROUP_DETAIL = 18;

    public $timestamps = false;
    protected $guarded = ['id'];
}
