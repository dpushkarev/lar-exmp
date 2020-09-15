<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlertErrorTable extends Migration
{
    protected $table = 'errors';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->text('error')->comment('Error json string')->change();
        });
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
