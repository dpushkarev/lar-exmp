<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableAirports extends Migration
{
    public $table = 'airports';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('Id of airport');
            $table->char('code', 5)->comment('Code of city');
            $table->string('name', 40)->comment('Name of airport');
            $table->char('country_code', 2)->comment('Code of country');
            $table->char('state_code', 2)->nullable()->comment('State or province of airport');
            $table->char('metro_code', 3)->nullable()->comment('Metro code of airport');
            $table->char('city_code', 5)->comment('City code of airport');
            $table->enum('type', [1,2,3,4,5,6,7,8,9])->comment('Type of airport');
            $table->enum('host_service', ['Y', 'N'])->default('N')->comment('Host service');
            $table->decimal('latitude', 10, 8)->nullable()->comment('Latitude of airport');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Longitude of airport');
            $table->timestamps();

            $table->foreign('city_code', 'fk__airports__cities')->references('code')->on('cities')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->foreign('country_code', 'fk__airports__countries')->references('code')->on('countries')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unique(['code'], 'idx__unique__code');
            $table->index(['type'], 'idx__name__type');
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
