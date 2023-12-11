<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormNameDynamicValuesToRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laravel_uap_leform_records', function (Blueprint $table) {
            $table->longText('dynamic_form_name')->nullable();
            $table->longText('dynamic_form_name_values')->nullable();
            $table->longText('dynamic_form_name_with_values')->nullable();
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
            $table->dropColumn('dynamic_form_name');
            $table->dropColumn('dynamic_form_name_values');
            $table->dropColumn('dynamic_form_name_with_values');
        });
    }
}

