<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
CREATE TABLE leform_stats (
	id int(11) NOT NULL auto_increment,
	form_id int(11) NULL,
	impressions int(11) NULL default '0',
	submits int(11) NULL default '0',
	confirmed int(11) NULL default '0',
	payments int(11) NULL default '0',
	datestamp int(11) NULL,
	timestamp int(11) NULL,
	deleted int(11) NULL default '0',
	UNIQUE KEY  id (id)
);
 */

class CreateStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_stats', function (Blueprint $table) {
            $table->id();
            $table->integer('impressions')->default(0)->nullable();
            $table->integer('submits')->default(0)->nullable();
            $table->integer('confirmed')->default(0)->nullable();
            $table->integer('payments')->default(0)->nullable();
            $table->integer('datestamp')->nullable();
            $table->integer('timestamp')->nullable();
            $table->integer('deleted')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('form_id')->constrained('laravel_uap_leform_forms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laravel_uap_leform_stats', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });
        Schema::dropIfExists('laravel_uap_leform_stats');
    }
}
