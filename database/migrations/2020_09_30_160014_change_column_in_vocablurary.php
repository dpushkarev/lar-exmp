<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnInVocablurary extends Migration
{
    protected $table = 'vocabulary_names';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->string('name', 255)->comment('Name of row')->change();
        });

        DB::statement("ALTER TABLE " . $this->table . " MODIFY lang ENUM('en', 'sr') DEFAULT 'en'");
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
