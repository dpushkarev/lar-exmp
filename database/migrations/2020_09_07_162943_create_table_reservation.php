<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableReservation extends Migration
{

    protected $table = 'reservations';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('Reservation id');
            $table->string('transaction_id')->default(null)->comment('TransactionId of FT');
            $table->integer('flights_search_flight_info_id')->comment('Id of flights search info');
            $table->timestamps();

            $table->unique('flights_search_flight_info_id', 'idx_unique__flights_search_flight_info_id');
        });

        Schema::table('flights_search_flight_infos', function (Blueprint $table) {
            $table->dropColumn('booked');
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
        Schema::table('flights_search_flight_infos', function (Blueprint $table) {
            $table->tinyInteger('booked')->default(0)->comment('Already booked');
        });
    }
}
