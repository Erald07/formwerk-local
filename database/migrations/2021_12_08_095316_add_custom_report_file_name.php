<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomReportFileName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('laravel_uap_leform_records', function (Blueprint $table) {
            $table->longText('custom_report_file_name')->nullable();
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
            $table->dropColumn('custom_report_file_name');
        });
    }
}
