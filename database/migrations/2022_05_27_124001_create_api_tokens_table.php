<?php

use App\Models\ApiToken;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateApiTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('app_key');
            $table->string('key');
            $table->string('secret');
        });

        $data = DB::connection('parser')->table('api_tokens')->get();
        foreach($data as $token) {
            ApiToken::create([
                'app_key' => $token->app_key,
                'key' => $token->key,
                'secret' => $token->secret
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
        Schema::dropIfExists('api_tokens');
    }
}
