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
        https://dm3nz_ya_ru:3d360c117a@45.134.28.19:30016";
        $array = explode("\n", $proxies);
        foreach($array as $item) {
            $item = explode("https://", $item);
            $item = $item[1];
            $temp = explode('@', $item);
            $ip = $item[1];
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
