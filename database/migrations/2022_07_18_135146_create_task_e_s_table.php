<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskESTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_e_s', function (Blueprint $table) {
            $table->id();
            $table->string('ibd');
            $table->boolean('is_vk')->default(false);
            $table->string('name')->nullable();
            $table->string('postUrl')->nullable();
            $table->string('gender')->nullable();
            $table->string('age')->nullable();
            $table->string("postId");
            $table->string('location')->nullable();
            $table->string("education")->nullable();
            $table->string("profileId");
            $table->string("profileUrl")->nullable();
            $table->text("commentText")->nullable();
            $table->string("activityType"); // like/comment
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
        Schema::dropIfExists('task_e_s');
    }
}
