<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableVocabularyName extends Migration
{
    public $table = 'vocabulary_names';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id')->comment('Id of row');
            $table->string('name', 40)->comment('Name of row');
            $table->enum('synonym', ['S', 'C'])->nullable()->comment('Synonym record');
            $table->integer('nameable_id')->comment('Id of morph');
            $table->string('nameable_type', 50)->comment('Type of morph');
            $table->enum('lang', ['en', 'rs'])->default('en')->comment('Lang');
            $table->timestamps();

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
