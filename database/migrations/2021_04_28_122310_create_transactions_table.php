<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
CREATE TABLE leform_transactions (
	id int(11) NULL AUTO_INCREMENT,
	record_id int(11) NULL,
	provider varchar(63) COLLATE utf8_unicode_ci NULL,
	payer_name varchar(255) COLLATE utf8_unicode_ci NULL,
	payer_email varchar(255) COLLATE utf8_unicode_ci NULL,
	gross float NULL,
	currency varchar(15) COLLATE utf8_unicode_ci NULL,
	payment_status varchar(63) COLLATE utf8_unicode_ci NULL,
	transaction_type varchar(63) COLLATE utf8_unicode_ci NULL,
	txn_id varchar(255) COLLATE utf8_unicode_ci NULL,
	details text COLLATE utf8_unicode_ci NULL,
	created int(11) NULL,
	deleted int(11) NULL DEFAULT '0',
	UNIQUE KEY id (id)
);
 */

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laravel_uap_leform_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 63)->nullable();
            $table->string('payer_name', 255)->nullable();
            $table->string('payer_email', 255)->nullable();
            $table->float('gross')->nullable();
            $table->string('currency', 15)->nullable();
            $table->string('payment_status', 63)->nullable();
            $table->string('transaction_type', 63)->nullable();
            $table->string('txn_id', 255)->nullable();
            $table->text('details')->nullable();
            $table->integer('created')->nullable();
            $table->integer('deleted')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('record_id'); # gonna be turned to foreign keys most likely
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('laravel_uap_leform_transactions');
    }
}
