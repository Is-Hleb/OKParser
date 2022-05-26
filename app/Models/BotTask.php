<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotTask extends Model
{
    use HasFactory;
    
    protected $connection= 'bot';
    protected $table = 'vk_task';
    protected $fillable = ['dataT', 'status_task', 'answer', 'user_id'];
    public $timestamps = false;

    public static function getQeuueTasks()
    {
        return self::where('user_id', 'OK')->where('status', '')->get();
    }
}
