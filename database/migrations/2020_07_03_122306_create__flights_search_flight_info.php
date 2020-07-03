<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlightsSearchFlightInfo extends Migration
{
    public $table = 'flights_search_flight_info';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('Used as order id');
            $table->string('transaction_id')->default(null)->comment('TransactionId of FT');
            $table->integer('flight_search_result_id')->comment('Id of flight_search_result');
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
        Schema::dropIfExists($this->table);
    }
}
