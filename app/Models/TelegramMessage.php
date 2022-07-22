<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $type
 * @property string $content
 * @property string $file_name
 */
class TelegramMessage extends Model
{
    protected $guarded = ['id'];
    use HasFactory;
}
