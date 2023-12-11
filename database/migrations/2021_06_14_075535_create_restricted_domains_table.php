<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestrictedDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restricted_domains', function (Blueprint $table) {
            $table->id();
            $table->string("domain");
            $table->foreignId('access_token_id')->constrained('access_tokens');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restricted_domains', function (Blueprint $table) {
            $table->dropForeign(['access_token_id']);
        });
        Schema::dropIfExists('restricted_domains');
    }
}
