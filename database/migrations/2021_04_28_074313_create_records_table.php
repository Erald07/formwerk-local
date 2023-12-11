<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
CREATE TABLE leform_records (
	id int(11) NOT NULL auto_increment,
	form_id int(11) NULL,
	personal_data_keys longtext collate utf8_unicode_ci NULL,
	unique_keys longtext collate utf8_unicode_ci NULL,
	fields longtext collate utf8_unicode_ci NULL,
	info longtext collate utf8_unicode_ci NULL,
	status int(11) NULL default '0',
	str_id varchar(31) collate latin1_general_cs NULL,
	gateway_id int(11) NULL,
	amount float NULL,
	currency varchar(7) COLLATE utf8_unicode_ci NULL,
	created int(11) NULL,
	deleted int(11) NULL default '0',
	UNIQUE KEY  id (id)
);
 */

class CreateRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_records', function (Blueprint $table) {
            $table->id();
            $table->longText('personal_data_keys')->nullable();
            $table->longText('unique_keys')->nullable();
            $table->longText('fields')->nullable();
            $table->longText('info')->nullable();
            $table->integer('status')->default(0)->nullable();
            $table->string('str_id', 31)->nullable()->collation('latin1_general_cs');
            $table->integer('gateway_id')->nullable();
            $table->float('amount')->nullable();
            $table->string('currency', 7)->nullable();
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
        Schema::table('laravel_uap_leform_records', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });
        Schema::dropIfExists('laravel_uap_leform_records');
    }
}
