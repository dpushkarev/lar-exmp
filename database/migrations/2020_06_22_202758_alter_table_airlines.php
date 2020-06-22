<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAirlines extends Migration
{
    public $table = 'airlines';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\Airline::query()->truncate();

        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->string('logo', 255)->nullable()->change();
            $table->string('rating', 12)->nullable()->after('logo');
            $table->string('monochromeLogo', 255)->nullable()->after('logo');
            $table->string('colors', 128)->nullable()->after('logo');
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
            $table->dropColumn('rating');
            $table->dropColumn('monochromeLogo');
            $table->dropColumn('colors');
            $table->smallInteger('width')->nullable()->comment('Width of image');
            $table->smallInteger('height')->nullable()->comment('Height of image');
        });
    }
}
