<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SpiderController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Web\IndexController;
use App\Models\JobInfo;
use App\Http\Controllers\TasksController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/task', TasksController::class);

\Illuminate\Support\Facades\Auth::routes();
Route::group(['middleware' => 'auth', 'as' => 'cron.', 'prefix' => '/cron'], function(){
    Route::get('/', [\App\Http\Controllers\Web\CronController::class, 'show']);
    Route::post('/post/links', [\App\Http\Controllers\Web\CronController::class, 'postLinks'])->name('post.links');
    Route::get('/post/output/{mode}/{tab}', [\App\Http\Controllers\Web\CronController::class, 'postOutput'])->name('post.output');
    Route::put('/cron/stop/{id}', [\App\Http\Controllers\Web\CronController::class, 'stopCron'])->name('stop');
});
