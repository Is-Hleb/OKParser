<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaskIdInJobInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_infos', function (Blueprint $table) {
            $table->bigInteger('task_id')->nullable();
            $table->boolean('is_node_task')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_infos', function (Blueprint $table) {
            $table->dropColumn(['task_id', 'is_node_task']);

        });
    }
}
