<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoodleIdOnEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laravel_uap_leform_records', function (Blueprint $table) {
            $table->unsignedBigInteger('moodle_user_id')->nullable();
            $table->unsignedBigInteger('moodle_course_id')->nullable();
            $table->boolean("sent_to_moodle")->default(0);
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
            $table->dropColumn('moodle_user_id');
            $table->dropColumn('moodle_course_id');
            $table->dropColumn("sent_to_moodle");
        });
    }
}
