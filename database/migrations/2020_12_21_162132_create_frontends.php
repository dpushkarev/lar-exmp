<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFrontends extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('frontend_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 100);
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('travel_agency_id');
            $table->timestamps();

            $table->foreign('travel_agency_id', 'fk__travel_agency_id__frontend_domains__travel_agencies')->references('id')->on('travel_agencies')->onDelete('CASCADE');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('frontend_domains');
    }
}
