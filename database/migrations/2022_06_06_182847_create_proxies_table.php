<?php

use App\Models\Proxy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProxiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string("user");
            $table->string('password');
            $table->string('ip');
            $table->timestamps();
        });

        $proxies = "https://dm3nz_ya_ru:3d360c117a@213.232.118.32:30016
        https://dm3nz_ya_ru:3d360c117a@213.232.116.252:30016
        https://dm3nz_ya_ru:3d360c117a@213.232.117.130:30016
        https://dm3nz_ya_ru:3d360c117a@45.134.28.112:30016
        https://dm3nz_ya_ru:3d360c117a@45.134.28.19:30016
        https://dm3nz_ya_ru:3d360c117a@213.232.119.160:30016
https://dm3nz_ya_ru:3d360c117a@213.232.119.202:30016
https://dm3nz_ya_ru:3d360c117a@213.232.119.172:30016
https://dm3nz_ya_ru:3d360c117a@213.232.116.28:30016
https://dm3nz_ya_ru:3d360c117a@213.232.118.193:30016
https://dm3nz_ya_ru:3d360c117a@213.232.118.246:30016
https://dm3nz_ya_ru:3d360c117a@213.232.118.236:30016
https://dm3nz_ya_ru:3d360c117a@45.134.31.90:30016
https://dm3nz_ya_ru:3d360c117a@45.134.29.128:30016
https://dm3nz_ya_ru:3d360c117a@45.134.29.36:30016
https://dm3nz_ya_ru:3d360c117a@45.134.29.55:30016
https://dm3nz_ya_ru:3d360c117a@45.134.28.155:30016
https://dm3nz_ya_ru:3d360c117a@45.134.30.166:30016
https://dm3nz_ya_ru:3d360c117a@45.134.28.94:30016
https://dm3nz_ya_ru:3d360c117a@83.150.229.4:30016
https://dm3nz_ya_ru:3d360c117a@83.150.231.128:30016
https://dm3nz_ya_ru:3d360c117a@83.150.231.142:30016
https://dm3nz_ya_ru:3d360c117a@83.150.228.140:30016
https://dm3nz_ya_ru:3d360c117a@83.150.231.7:30016
https://dm3nz_ya_ru:3d360c117a@83.150.228.154:30016
https://dm3nz_ya_ru:3d360c117a@83.150.228.120:30016
https://dm3nz_ya_ru:3d360c117a@45.140.72.206:30016
https://dm3nz_ya_ru:3d360c117a@45.140.74.75:30016
https://dm3nz_ya_ru:3d360c117a@45.140.74.242:30016
https://dm3nz_ya_ru:3d360c117a@45.140.74.128:30016
https://dm3nz_ya_ru:3d360c117a@45.140.75.116:30016
https://dm3nz_ya_ru:3d360c117a@45.140.73.238:30016
https://dm3nz_ya_ru:3d360c117a@45.140.73.218:30016
https://dm3nz_ya_ru:3d360c117a@213.166.88.231:30016
https://dm3nz_ya_ru:3d360c117a@213.166.88.225:30016
https://dm3nz_ya_ru:3d360c117a@213.166.89.35:30016
https://dm3nz_ya_ru:3d360c117a@213.166.90.92:30016
https://dm3nz_ya_ru:3d360c117a@213.166.89.97:30016
https://dm3nz_ya_ru:3d360c117a@213.166.89.186:30016
https://3ggmwpUn:vHg8bFaw@45.140.62.98:59726
https://3ggmwpUn:vHg8bFaw@45.140.63.83:50279
https://3ggmwpUn:vHg8bFaw@45.159.84.42:56450
https://3ggmwpUn:vHg8bFaw@45.156.151.97:63212
https://3ggmwpUn:vHg8bFaw@45.153.55.104:58333
https://3ggmwpUn:vHg8bFaw@45.152.215.146:63466
https://3ggmwpUn:vHg8bFaw@45.159.87.52:52748
https://3ggmwpUn:vHg8bFaw@45.152.212.56:46382
https://3ggmwpUn:vHg8bFaw@45.156.148.35:54215
https://3ggmwpUn:vHg8bFaw@45.139.55.230:48490
https://dm3nz_ya_ru:3d360c117a@213.166.89.39:30016";
        $array = explode("\n", $proxies);
        foreach($array as $item) {
            $item = explode("https://", $item);
            $item = $item[1];
            $temp = explode("@", $item);
            $ip = $temp[1];
            dump($ip);
            $user_password = explode(':', $temp[0]);
            $user = $user_password[0];
            $password = $user_password[1];

            Proxy::create([
                'user' => $user,
                'password' => $password,
                'ip' => $ip
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proxies');
    }
}
