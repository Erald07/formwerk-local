<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShareMetaDataToForm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laravel_uap_leform_forms', function (Blueprint $table) {
            $table->timestamp('share_date')->nullable();
            $table->unsignedBigInteger('share_form_id')->nullable();
            $table->unsignedBigInteger('share_user_id')->nullable();
            $table->unsignedBigInteger('share_company_id')->nullable();
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
            $table->dropColumn('share_date');
            $table->dropColumn('share_form_id');
            $table->dropColumn('share_user_id');
            $table->dropColumn('share_company_id');
        });
    }
}
