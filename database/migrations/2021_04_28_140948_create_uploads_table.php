<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
CREATE TABLE leform_uploads (
	id int(11) NOT NULL auto_increment,
	record_id int(11) NULL,
	form_id int(11) NULL,
	element_id int(11) NULL,
	upload_id varchar(63) collate latin1_general_cs NULL,
	str_id varchar(63) collate latin1_general_cs NULL,
	status int(11) NULL,
	message longtext collate utf8_unicode_ci NULL,
	filename varchar(255) collate utf8_unicode_ci NULL,
	filename_original varchar(255) collate utf8_unicode_ci NULL,
	created int(11) NULL,
	deleted int(11) NULL default '0',
	file_deleted int(11) NULL default '0',
	UNIQUE KEY  id (id)
);
 */

class CreateUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_id')->nullable(); # gonna be turned to foreign keys most likely
            $table->unsignedBigInteger('element_id')->nullable(); # gonna be turned to foreign keys most likely
            $table->string('upload_id', 63)->nullable()->collation('latin1_general_cs');
            $table->string('str_id', 63)->nullable()->collation('latin1_general_cs');
            $table->integer('status')->nullable();
            $table->longText('message')->nullable();
            $table->string('filename', 255)->nullable();
            $table->string('filename_original', 255)->nullable();
            $table->integer('created')->nullable();
            $table->integer('deleted')->default(0)->nullable();
            $table->integer('file_deleted')->default(0)->nullable();
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
        Schema::table('laravel_uap_leform_uploads', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });
        Schema::dropIfExists('laravel_uap_leform_uploads');
    }
}
