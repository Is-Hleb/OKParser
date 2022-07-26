<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParserTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parser_tasks', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->string('status')->default(\App\Models\JobInfo::WAITING);
            $table->foreignId('type_id')->references('id')->on('parser_types')->onDelete('cascade');
            $table->string("table_name")->nullable();
            $table->json("columns")->nullable();
            $table->bigInteger('task_id')->nullable();
            $table->bigInteger("rows_count")->nullable();
            $table->string("output_path")->nullable();
            $table->bigInteger('parser_id')->nullable();
            $table->string('selected_table')->nullable();
            $table->boolean('is_asup_task')->default(false);
            $table->string("logins")->nullable();
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
        Schema::dropIfExists('parser_tasks');
    }
}
