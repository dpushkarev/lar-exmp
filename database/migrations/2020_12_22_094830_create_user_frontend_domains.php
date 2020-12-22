<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserFrontendDomains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_frontend_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('frontend_domain_id');
            $table->timestamps();

            $table->unique(['user_id', 'frontend_domain_id'], 'idx__unique__user_id__frontend_domain_id');

            $table->foreign('user_id', 'fk__ufd__users')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreign('frontend_domain_id', 'fk__ufd__frontend_domains')->references('id')->on('frontend_domains')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_frontend_domains');
    }
}
