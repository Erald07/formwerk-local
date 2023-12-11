<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
CREATE TABLE leform_forms (
  id int(11) NOT NULL auto_increment,
  name varchar(255) collate utf8_unicode_ci NULL,
  options longtext collate utf8_unicode_ci NULL,
  pages longtext collate utf8_unicode_ci NULL,
  elements longtext collate utf8_unicode_ci NULL,
  cache_style longtext collate utf8_unicode_ci NULL,
  cache_html longtext collate utf8_unicode_ci NULL,
  cache_uids longtext collate utf8_unicode_ci NULL,
  cache_time int(11) NULL default '0',
  active int(11) NULL default '1',
  created int(11) NULL,
  modified int(11) NULL,
  deleted int(11) NULL default '0',
  UNIQUE KEY  id (id)
);
 */

class CreateFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable();
            $table->longText('options')->nullable();
            $table->longText('pages')->nullable();
            $table->longText('elements')->nullable();
            $table->longText('cache_style')->nullable();
            $table->longText('cache_html')->nullable();
            $table->longText('cache_uids')->nullable();
            $table->integer('cache_time')->default(0)->nullable();
            $table->integer('active')->default(1)->nullable();
            $table->integer('created')->nullable();
            $table->integer('modified')->nullable();
            $table->integer('deleted')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('company_id')->constrained('companies');
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
            $table->dropForeign(['user_id']);
            $table->dropForeign(['company_id']);
        });
        Schema::dropIfExists('laravel_uap_leform_forms');
    }
}
