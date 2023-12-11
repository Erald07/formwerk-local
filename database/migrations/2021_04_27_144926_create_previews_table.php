<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
CREATE TABLE leform_previews (
	id int(11) NOT NULL auto_increment,
	form_id int(11) NULL default '0',
	name varchar(255) collate utf8_unicode_ci NULL,
	options longtext collate utf8_unicode_ci NULL,
	pages longtext collate utf8_unicode_ci NULL,
	elements longtext collate utf8_unicode_ci NULL,
	created int(11) NULL,
	deleted int(11) NULL default '0',
	UNIQUE KEY  id (id)
);
 */

class CreatePreviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_previews', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable();
            $table->longText('options')->nullable();
            $table->longText('pages')->nullable();
            $table->longText('elements')->nullable();
            $table->integer('created')->nullable();
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
        Schema::table('laravel_uap_leform_previews', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });
        Schema::dropIfExists('laravel_uap_leform_previews');
    }
}
