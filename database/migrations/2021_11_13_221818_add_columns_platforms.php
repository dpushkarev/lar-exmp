<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsPlatforms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('platforms', function (Blueprint $blueprint) {
            $blueprint->decimal('cash_fee', '10', '2')->default(.0)->after('agency_fee_default');
            $blueprint->enum('cash_fee_type', ['fix', 'percent'])->default('fix')->after('cash_fee');
            $blueprint->decimal('intesa_fee', '10', '2')->default(.0)->after('cash_fee_type');
            $blueprint->enum('intesa_fee_type', ['fix', 'percent'])->default('fix')->after('intesa_fee');
        });
        Schema::table('platform_rules', function (Blueprint $blueprint) {
            $blueprint->dropColumn('cash_fee');
            $blueprint->dropColumn('cash_fee_type');
            $blueprint->dropColumn('intesa_fee');
            $blueprint->dropColumn('intesa_fee_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('platforms', function (Blueprint $blueprint) {
            $blueprint->dropColumn('cash_fee');
            $blueprint->dropColumn('cash_fee_type');
            $blueprint->dropColumn('intesa_fee');
            $blueprint->dropColumn('intesa_fee_type');
        });
        Schema::table('platform_rules', function (Blueprint $blueprint) {
            $blueprint->decimal('cash_fee', '10', '2')->default(.0)->after('brand_fee_type');
            $blueprint->enum('cash_fee_type', ['fix', 'percent'])->default('fix')->after('cash_fee');
            $blueprint->decimal('intesa_fee', '10', '2')->default(.0)->after('cash_fee_type');
            $blueprint->enum('intesa_fee_type', ['fix', 'percent'])->default('fix')->after('intesa_fee');
        });
    }
}
