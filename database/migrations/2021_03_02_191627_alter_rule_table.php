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
            $table->dropForeign('frontend_domain_rules_frontend_domain_id_foreign');
            $table->renameColumn('frontend_domain_id', 'platform_id');
            $table->foreign('platform_id', 'frontend_domain_rules_platform_id_foreign')->on('platforms')->references('id')->onDelete('CASCADE');
        });

        Schema::table('platform_rules', function (Blueprint $table) {
            $table->enum('trip_type', ['one_way', 'return', 'multi'])->default('return')->after('platform_id');
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
            $table->dropForeign('frontend_domain_rules_platform_id_foreign');
            $table->renameColumn('platform_id', 'frontend_domain_id');
        });
        Schema::rename('platforms', 'frontend_domains');
        Schema::rename('platform_rules', 'frontend_domain_rules');
        Schema::table('frontend_domain_rules', function (Blueprint $table) {
            $table->foreign('frontend_domain_id', 'frontend_domain_rules_frontend_domain_id_foreign')->on('frontend_domains')->references('id')->onDelete('CASCADE');
        });
    }
}
