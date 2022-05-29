<?php

use App\Models\OkUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOkUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = [
            '79266526842:1234576',
            '79605529357:2iGCVWsya',
            '79267618619:xzg5tuNp',
            '79171447430:6MPqZR5SQJXPY',
            '79171447581:STcB0dzAK',
            '79266547476:ZqMyKWsx8nex6',
            '79171420690:papXhYHCx2hjM'
        ];

        Schema::create('ok_users', function (Blueprint $table) {
            $table->id();
            $table->string('login');
            $table->string('password');
            $table->json('cookies')->nullable();
        });

        foreach($users as $user) {
            $credits = explode(':', $user);
            OkUser::create([
                'login' => $credits[0],
                'password' => $credits[1]
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
        Schema::dropIfExists('ok_users');
    }
}
