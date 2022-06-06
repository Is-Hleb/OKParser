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
            '79171420690:papXhYHCx2hjM',
            '6283198636292:QZ0cEv',
            '6283157156557:qZ9OpD',
            '6283177387664:SIc8LP',
            '6283199094220:W4dohY',
            '6285947487401:6Dmxhb',
            '6283159418679:eL0ATr',
            '6283874886555:NJ5vGo',
            '6283899039793:qHV5ic',
            '6283157737473:5Nvu2T',
            '6283899177543:Pj4s4q',
            '6283182108387:p3Deki',
            '6283899483567:1IbmGd',
            '6283874889537:Tn62o0',
            '6283899176334:pO4OAl',
            '6283167732030:1HeVC8',
            '6283198635283:2gyMls',
            '6283899488800:7f9J2z',
            '6287738865062:cA5wZa',
            '6283899174541:4VxUnT',
            '6283899172461:Em0O5L',
            '6283199098329:nc6BqH',
            '6283124607006:1Kj12f',
            '6287816954545:B0MqQx',
            '6283121364857:Iy0mNR',
            '6283899037278:nT9fC6',
            '6283167733399:aCz921',
            '6283123885960:fb8Usq',
            '6283199094204:NcD1Xq',
            '6283899413293:bCk7cT',
            '6283876171010:krP8qm'
        ];
        $content = explode("\n", file_get_contents("users.txt"));
        foreach($content as $data) {
            $arr = explode(":", $data);
            $users[] = $arr[0] . ":" . $arr[1];
        }
        Schema::create('ok_users', function (Blueprint $table) {
            $table->id();
            $table->string('login');
            $table->string('password');
            $table->json('cookies')->nullable();
        });

        foreach ($users as $user) {
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
