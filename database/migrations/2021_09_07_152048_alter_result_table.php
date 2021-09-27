<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterResultTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flights_search_results', function (Blueprint $table) {
            $table->integer('rule_id')->after('segments')->nullable()->comment('Id of rule');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('flights_search_results', function (Blueprint $table) {
            $table->dropColumn('rule_id');
        });
    }
}
