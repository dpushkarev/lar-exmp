<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('frontend_domains', 'platforms');
        Schema::rename('frontend_domain_rules', 'platform_rules');
        Schema::table('platform_rules', function (Blueprint $table) {
            $table->dropColumn('trip_types');
            $table->renameColumn('frontend_domain_id', 'platform_id');
        });

        Schema::table('platform_rules', function (Blueprint $table) {
            $table->enum('trip_types', ['one_way', 'return', 'multi'])->default('return')->after('platform_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('platform_rules', function (Blueprint $table) {
            $table->renameColumn('platform_id', 'frontend_domain_id');
        });
        Schema::rename('platforms', 'frontend_domains');
        Schema::rename('platform_rules', 'frontend_domain_rules');
    }
}
