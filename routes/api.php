<?php

use App\Http\Controllers\Api\ActionController;
use App\Http\Controllers\Api\CronController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/', ActionController::class);
Route::post('/cron', CronController::class);
