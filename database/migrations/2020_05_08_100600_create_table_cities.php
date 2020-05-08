<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableCities extends Migration
{
    public $table = 'cities';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('Id of city');
            $table->char('code', 5)->comment('Code of city');
            $table->enum('synonym', ['S', 'C'])->nullable()->comment('Synonym record');
            $table->string('name', 30)->comment('Name of city');
            $table->string('origin_name', 30)->nullable()->comment('Filled if it is synonym');
            $table->char('country_code', 2)->comment('Code of country');
            $table->char('state_code', 2)->nullable()->comment('State or province of city');
            $table->char('metro_code', 3)->nullable()->comment('Metro code of city');
            $table->string('associated_airports', 255)->nullable()->comment('Codes of associated airports');
            $table->enum('host_service', ['Y', 'N'])->default('N')->comment('Host service');
            $table->enum('commercial_service', ['Y', 'N'])->default('N')->comment('Commercial service');
            $table->timestamps();

            $table->foreign('country_code', 'fk__cities__countries')->references('code')->on('countries')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unique(['code', 'name'], 'idx__unique__code__name');
            $table->index('name', 'idx__name');
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
