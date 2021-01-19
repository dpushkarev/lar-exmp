<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFrontendDomainRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('frontend_domain_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('origin_id')->nullable();
            $table->unsignedInteger('destination_id')->nullable();
            $table->set('cabin_classes', ['economy', 'business', 'first', 'premium_economy'])->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->timestamps();

            $table->foreign('origin_id')->on('vocabulary_names')->references('id')->onDelete('CASCADE');
            $table->foreign('destination_id')->on('vocabulary_names')->references('id')->onDelete('CASCADE');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('frontend_domain_rules');
    }
}
