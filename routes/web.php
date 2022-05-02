<?php

use Illuminate\Support\Facades\Route;

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

use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    for($i = 1; $i <= 6; $i--) {
        echo "<h$i>Привет Дашуля :)</h$i>";
    }
});

//Route::get('/', function (\App\Services\OKApi $service) {
//
//    $loginUrl = 'https://ok.ru/dk?st.cmd=anonymMain&st.layer.cmd=PopLayerClose';
//    $login = "79269337405";
//    $password = "L0zASJsiuaN6l";
//
//    $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.2.13) ' .
//        'Gecko/20101203 Firefox/3.6.13 ( .NET CLR 3.5.30729)';
//
//
//    $post = array(
//        'st.redirect' => '',
//        'st.posted' => 'set',
//        'st.email' => $login,
//        'st.password' => $password,
//        'st.screenSize' => '',
//        'st.browserSize' => '',
//        'st.flashVer' => ''
//    );
//
//    $response = Http::withUserAgent($user_agent)->post($loginUrl, $post);
//    $cookies = $response->cookies()->toArray();
//    $cookies = array_filter($cookies, fn ($value) => $value['Name'] === 'bci' || $value['Name'] === '_statid');
//
//    $output = [];
//    foreach ($cookies as $cookie) {
//        $output[$cookie['Name']] = $cookie['Value'];
//    }
//
//    dump($response->header('set-cookie'), $cookies);
//
//
//    $cookiesJar = \GuzzleHttp\Cookie\CookieJar::fromArray($output, '.ok.ru');
//
//    $response = Http::withUserAgent($user_agent)
//        ->withCookies($output, '.ok.ru')
//        ->get('https://ok.ru/profile/514677397371/subscribers',);
//    echo $response->body();
//});
//
