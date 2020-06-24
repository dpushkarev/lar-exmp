<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAircrafts extends Migration
{
    public $table = 'aircrafts';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('Id of aircraft');
            $table->char('code', 4)->comment('Equipment Code of aircraft');
            $table->string('name', 50)->comment('Name of aircraft');
            $table->string('short_name', 35)->comment('Name of aircraft');
            $table->timestamps();

            $table->index('code', 'idx__code');
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
