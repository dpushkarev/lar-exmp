<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsIntoReservation extends Migration
{
    protected $table = 'reservations';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table){
            $table->unsignedDecimal('amount', 15, 2)->after('data')->comment('Amount of reservation');
            $table->char('currency_code', 3)->after('amount')->comment('Currency of amount');
            $table->tinyInteger('is_paid')->after('currency_code')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->table, function (Blueprint $table){
            $table->dropColumn('amount');
            $table->dropColumn('currency_code');
            $table->dropColumn('is_paid');
        });
    }
}
