<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeRecordsFieldType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laravel_uap_leform_records', function (Blueprint $table) {
            $table->text('primary_field_value')->change();
            $table->text('secondary_field_value')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('laravel_uap_leform_records', function (Blueprint $table) {
            $table->string('primary_field_value')->nullable();
            $table->string('secondary_field_value')->nullable();
        });    
    }
}
