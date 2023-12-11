<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormBackgroundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_background', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('laravel_uap_leform_forms');
            $table->string('filename', 255);
            $table->string('filename_original', 255);
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
        Schema::table('form_background', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });
        Schema::dropIfExists('form_background');
    }
}
