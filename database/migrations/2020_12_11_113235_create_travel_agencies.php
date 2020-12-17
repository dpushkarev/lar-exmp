<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTravelAgencies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('travel_agencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 128);
            $table->timestamps();
        });

        Schema::create('user_travel_agencies', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('travel_agency_id');
            $table->timestamps();

            $table->unique(['user_id', 'travel_agency_id']);

            $table->foreign('user_id', 'fk__user_id__users')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreign('travel_agency_id', 'fk__travel_agency_id__travel_agencies')->references('id')->on('travel_agencies')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('travel_agencies');
        Schema::dropIfExists('user_travel_agencies');
    }
}
