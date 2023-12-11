<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdOnEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('laravel_uap_leform_records', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained('companies');
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::disableForeignKeyConstraints();

        Schema::table('laravel_uap_leform_records', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
        Schema::enableForeignKeyConstraints();
        //
    }
}
