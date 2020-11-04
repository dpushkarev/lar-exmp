<?php

use Illuminate\Database\Migrations\Migration;

class ChangeReservationField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE reservations
	        CHANGE COLUMN access_code access_code CHAR(6) comment 'Code of reservation'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        return;
    }
}
