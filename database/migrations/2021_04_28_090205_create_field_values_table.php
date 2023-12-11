<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFieldValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_fieldvalues', function (Blueprint $table) {
            $table->id();
            $table->longText('value')->nullable();
            $table->integer('datestamp')->nullable();
            $table->integer('deleted')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('form_id')->constrained('laravel_uap_leform_forms');
            $table->unsignedBigInteger('record_id')->nullable(); # gonna be turned to foreign keys most likely
            $table->unsignedBigInteger('field_id')->nullable(); # gonna be turned to foreign keys most likely
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laravel_uap_leform_fieldvalues', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });
        Schema::dropIfExists('laravel_uap_leform_fieldvalues');
    }
}
