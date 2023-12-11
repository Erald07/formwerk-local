<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
CREATE TABLE leform_validations (
	id int(11) NOT NULL auto_increment,
	type varchar(15) collate latin1_general_cs NULL,
	hash varchar(63) collate latin1_general_cs NULL,
	valid int(11) NULL default '0',
	created int(11) NULL,
	UNIQUE KEY  id (id)
);
 */

class CreateValidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_validations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 15)->nullable()->collation('latin1_general_cs');
            $table->string('hash', 63)->nullable()->collation('latin1_general_cs');
            $table->integer('valid')->default(0)->nullable();
            $table->integer('created')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('laravel_uap_leform_validations');
    }
}
