<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableCountries extends Migration
{
    public $table = 'countries';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('Id of country');
            $table->char('code', 2)->comment('Code of country');
            $table->string('name', 35)->comment('Name of country');
            $table->char('currency_code', 3)->comment('Code of currency');
            $table->smallInteger('postal_code')->default(0)->comment('Post code of county');
            $table->enum('associated', ['Y', 'N'])->default('Y')->comment('Associated city or airport');
            $table->timestamps();

            $table->unique('code', 'idx__unique__code');
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
