<?php

use App\Http\Controllers\ParserToolsController;
use App\Http\Controllers\Web\CronController;
use App\Http\Controllers\Web\UsersByCity;
use App\Http\Controllers\Web\UsersFriendsSubscribersController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\Api\SetTaskController;

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
Route::post('/v2/task', SetTaskController::class);
Route::post('/vk/task/3/data', \App\Http\Controllers\Api\VkTaskEController::class);

\Illuminate\Support\Facades\Auth::routes();
Route::group(['middleware' => 'auth', 'as' => 'cron.', 'prefix' => '/cron'], function () {
    Route::get('/', [CronController::class, 'show'])->name('show');
    Route::post('/post/links', [CronController::class, 'postLinks'])->name('post.links');
    Route::get('/post/output/{mode}/{tab}', [CronController::class, 'postOutput'])->name('post.output');
    Route::put('/cron/stop/{id}', [CronController::class, 'stopCron'])->name('stop');
});

Route::group(['middleware' => 'auth', 'as' => 'job.', 'prefix' => '/job'], function () {
    Route::get('/users-by-cities', UsersByCity::class)->name('users-by-cities');
    Route::post('/users-by-cities', UsersByCity::class);
    Route::get('/users-by-cities/export/{table_name}/{job_id}', [UsersByCity::class, 'export'])->name('users-by-cities.export');
    Route::post('/users-by-cities/update-status', [UsersByCity::class, 'updateStatus'])->name('users-by-cities.update-status');
    Route::delete('/users-by-cities/delete/{job_id}', [UsersByCity::class, 'delete'])->name('users-by-cities.delete');
    Route::get('/users-by-cities/parser_again/{job_id}', [UsersByCity::class, 'parseAgain'])->name('users-by-cities.parser_again');


    Route::get('/users-subscribers', UsersFriendsSubscribersController::class)->name('users-friends-subscribers.show');
    Route::get('/users-subscribers/task/{type}/{job_id}', [UsersFriendsSubscribersController::class, 'setTask'])->name('users-friends-subscribers.set-task');
    Route::get('/users-subscribers/task/{task_id}', [UsersFriendsSubscribersController::class, 'export'])->name('users-friends-subscribers.export');
});

Route::group(['middleware' => 'auth', 'as' => 'tools.', 'prefix' => '/tools'], function () {
    Route::put('/proxy/reset', [ParserToolsController::class, 'resetProxies'])->name('reset.proxies');
    Route::put('/users/reset', [ParserToolsController::class, 'resetUsers'])->name('reset.users');
});

Route::group(['middleware' => 'auth', 'as' => 'parser.ui.', 'prefix' => '/parser/ui'], function () {
    Route::get('/task', \App\Http\Controllers\Web\TaskController::class)->name('show');
    Route::post('/parser', [\App\Http\Controllers\Web\ParserController::class, 'create'])->name('parser.create');
    Route::post('/task', [\App\Http\Controllers\Web\TaskController::class, 'create'])->name('task.create');
    Route::get('/task/export/{task_id}', [\App\Http\Controllers\Web\TaskController::class, 'export'])->name('task.export');
    Route::get('/task/download/{task_id}', [\App\Http\Controllers\Web\TaskController::class, 'download'])->name('task.download');
    Route::get('/task/export/stats/{task_id}', [\App\Http\Controllers\Web\TaskController::class, 'exportStats']);
});

Route::group(['as' => 'parser.', 'prefix' => '/parser'], function () {
    Route::get('/task/{token}', [\App\Http\Controllers\Api\ParserController::class, 'getTask']);
    Route::put('/task/', [\App\Http\Controllers\Api\ParserController::class, 'taskRunning']);
    Route::put('/task/{id}', [\App\Http\Controllers\Api\ParserController::class, 'updateCount']);
    Route::delete('/task/{id}', [\App\Http\Controllers\Api\ParserController::class, 'finishTask']);
    Route::get('/task/all/{token}', [\App\Http\Controllers\Api\ParserController::class, 'allParserTasks']);
});

Route::redirect('/', '/parser/ui/task');
