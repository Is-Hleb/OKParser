<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SpiderController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Web\IndexController;
use App\Models\JobInfo;

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
Route::get('/parse', SpiderController::class);

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::group(['middleware' => 'auth'], function(){
    Route::get('/', IndexController::class)->name('home');
    Route::get('/job/delete/{job}', function(int $job) {
        JobInfo::destroy($job); 
        return redirect()->route('home');
    })->name('job.delete');
});


Route::get('/test', TestController::class);