<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInInfoResTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flights_search_flight_infos', function (Blueprint $table) {
            $table->char('code',10)->after('id')->comment('Code of checkout');
            $table->unique('code', 'idx__unique__code');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->char('code',10)->after('id')->comment('Code of reservation');
            $table->char('access_code',5)->after('code')->comment('Code of reservation');
            $table->unique('code', 'idx__unique__code');
        });
    }

    /**
     * Reverse the migrations.не
     *
     * @return void
     */
    public function down()
    {
        Schema::table('flights_search_flight_infos', function (Blueprint $table) {
            $table->dropIndex('idx__unique__code');
            $table->dropColumn('code');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('idx__unique__code');
            $table->dropColumn('code');
            $table->dropColumn('access_code');
        });
    }
}
