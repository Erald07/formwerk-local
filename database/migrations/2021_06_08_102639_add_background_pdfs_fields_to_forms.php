<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBackgroundPdfsFieldsToForms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laravel_uap_leform_forms', function (Blueprint $table) {
            $table->unsignedInteger('first_page_pdf_background_id')
                ->nullable();
            $table->unsignedInteger('other_page_pdf_background_id')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laravel_uap_leform_forms', function (Blueprint $table) {
            $table->dropColumn('first_page_pdf_background_id');
            $table->dropColumn('other_page_pdf_background_id');
        });
    }
}
