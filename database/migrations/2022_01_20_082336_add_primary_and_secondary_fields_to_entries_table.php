<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryAndSecondaryFieldsToEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laravel_uap_leform_records', function (Blueprint $table) {
            $table->string('primary_field_id')->nullable();
            $table->string('primary_field_value')->nullable();
            $table->string('secondary_field_id')->nullable();
            $table->string('secondary_field_value')->nullable();
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
            $table->dropColumn('primary_field_id');
            $table->dropColumn('primary_field_value');
            $table->dropColumn('secondary_field_id');
            $table->dropColumn('secondary_field_value');
        });
    }
}
