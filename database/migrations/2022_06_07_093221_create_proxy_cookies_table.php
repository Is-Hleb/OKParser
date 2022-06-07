<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProxyCookiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proxy_cookies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ok_user_id')->references('id')->on('ok_users')->onDelete('cascade');
            $table->foreignId('proxy_id')->references('id')->on('proxies')->onDelete('cascade');
            $table->json('cookies');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proxy_cookies');
    }
}
