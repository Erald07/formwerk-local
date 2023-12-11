<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
CREATE TABLE leform_webfonts (
	id int(11) NOT NULL auto_increment,
	family varchar(255) collate utf8_unicode_ci NULL,
	variants varchar(255) collate utf8_unicode_ci NULL,
	subsets varchar(255) collate utf8_unicode_ci NULL,
	source varchar(31) collate latin1_general_cs NULL,
	deleted int(11) NULL default '0',
	UNIQUE KEY  id (id)
);
 */

class CreateWebfontsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_webfonts', function (Blueprint $table) {
            $table->id();
            $table->string('family', 255)->nullable();
            $table->string('variants', 255)->nullable();
            $table->string('subsets', 255)->nullable();
            $table->string('source', 31)->nullable()->collation('latin1_general_cs');
            $table->integer('deleted')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
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
        Schema::table('laravel_uap_leform_webfonts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
        Schema::dropIfExists('laravel_uap_leform_webfonts');
    }
}
