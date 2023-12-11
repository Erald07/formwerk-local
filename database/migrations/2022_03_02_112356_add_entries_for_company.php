<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEntriesForCompany extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('record_file_deletion_cron_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('deleted_records')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('record_file_deletion_cron_jobs', function (Blueprint $table) {
            $table->dropColumn('deleted_records');
        });
    }
}
