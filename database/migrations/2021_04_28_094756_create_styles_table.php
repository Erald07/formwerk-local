<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
CREATE TABLE leform_styles (
	id int(11) NOT NULL auto_increment,
	name varchar(255) collate utf8_unicode_ci NULL,
	options longtext collate utf8_unicode_ci NULL,
	type int(11) NULL default '".esc_sql(LEFORM_STYLE_TYPE_USER)."',
	deleted int(11) NULL default '0',
	UNIQUE KEY  id (id)
);
*/

class CreateStylesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_styles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable();
            $table->longText('options')->nullable();
            $table->integer('type')->default(0)->nullable();
            $table->integer('deleted')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('user_id')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laravel_uap_leform_styles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['company_id']);
        });
        Schema::dropIfExists('laravel_uap_leform_styles');
    }
}
