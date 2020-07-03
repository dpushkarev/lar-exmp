<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterFlightsSearchResults extends Migration
{

    protected $table = 'flights_search_results';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->change();
            $table->dropColumn('transaction_id');
            $table->integer('flight_search_request_id')->comment('Id of flight_search_request')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->increments('id')->change();
            $table->dropColumn('flight_search_request_id');
            $table->string('transaction_id')->nullable()->default(null)->comment('TransactionId of FT')->after('id');
        });
    }
}
