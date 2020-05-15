<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAirlines extends Migration
{
    public $table = 'airlines';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('Id of airline');
            $table->char('code', 3)->comment('Code of airline');
            $table->string('name', 35)->comment('Name of airline');
            $table->string('short_name', 16)->comment('Short name of airline');
            $table->char('country_code', 3)->nullable()->comment('Code of country');
            $table->enum('participation', ['P', 'N'])->default('N')->comment('Apollo Participation');
            $table->enum('vendor_type', ['P', 'H', 'K', 'U'])->default('P')->comment('Vendor type of airline');
            $table->string('logo', 50)->nullable()->comment('Image name file');
            $table->smallInteger('width')->nullable()->comment('Width of image');
            $table->smallInteger('height')->nullable()->comment('Height of image');
            $table->timestamps();

//            $table->foreign('country_code', 'fk__airlines__countries')->references('code')->on('countries')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->unique('code', 'idx__unique__code');
            $table->index('name', 'idx__name');
            $table->index(['vendor_type', 'name'], 'idx__vendor_type__name');
            $table->index('short_name', 'idx__short_name');

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
