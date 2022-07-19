<?php

use App\Http\Controllers\ParserToolsController;
use App\Http\Controllers\Web\CronController;
use App\Http\Controllers\Web\UsersByCity;
use Illuminate\Support\Facades\Route;
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
Route::group(['middleware' => 'auth', 'as' => 'cron.', 'prefix' => '/cron'], function () {
    Route::get('/', [CronController::class, 'show'])->name('show');
    Route::post('/post/links', [CronController::class, 'postLinks'])->name('post.links');
    Route::get('/post/output/{mode}/{tab}', [CronController::class, 'postOutput'])->name('post.output');
    Route::put('/cron/stop/{id}', [CronController::class, 'stopCron'])->name('stop');
});

Route::group(['middleware' => 'auth', 'as' => 'job.', 'prefix' => '/job'], function(){
    Route::get('/users-by-cities', UsersByCity::class)->name('users-by-cities');
    Route::post('/users-by-cities', UsersByCity::class);
    Route::get('/users-by-cities/export/{table_name}/{job_id}', [UsersByCity::class, 'export'])->name('users-by-cities.export');
    Route::post('/users-by-cities/update-status', [UsersByCity::class, 'updateStatus'])->name('users-by-cities.update-status');
    Route::delete('/users-by-cities/delete/{job_id}', [UsersByCity::class, 'delete'])->name('users-by-cities.delete');
    Route::get('/users-by-cities/parser_again/{job_id}', [UsersByCity::class, 'parseAgain'])->name('users-by-cities.parser_again');
});

Route::group(['middleware' => 'auth', 'as' => 'tools.', 'prefix' => '/tools'], function(){
   Route::put('/proxy/reset', [ParserToolsController::class, 'resetProxies'])->name('reset.proxies');
    Route::put('/users/reset', [ParserToolsController::class, 'resetUsers'])->name('reset.users');
});

