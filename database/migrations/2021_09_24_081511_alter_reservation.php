<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterReservation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reservations', function (Blueprint $blueprint) {
            $blueprint->decimal('fee', 15, 2)->after('amount')->default(.0);
            $blueprint->decimal('total_price', 15, 2)->after('fee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservations', function (Blueprint $blueprint) {
            $blueprint->dropColumn('fee');
            $blueprint->dropColumn('total_price');
        });
    }
}
