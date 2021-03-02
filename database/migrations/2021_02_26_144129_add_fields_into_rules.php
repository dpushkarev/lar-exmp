<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsIntoRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('frontend_domain_rules', function (Blueprint $blueprint) {
            $blueprint->decimal('agency_fee', '10', '2')->default(.0)->after('max_amount');
            $blueprint->enum('agency_fee_type', ['fix', 'percent'])->default('fix')->after('agency_fee');
            $blueprint->decimal('brand_fee', '10', '2')->default(.0)->after('agency_fee_type');
            $blueprint->enum('brand_fee_type', ['fix', 'percent'])->default('fix')->after('brand_fee');
            $blueprint->decimal('cash_fee', '10', '2')->default(.0)->after('brand_fee_type');
            $blueprint->enum('cash_fee_type', ['fix', 'percent'])->default('fix')->after('cash_fee');
            $blueprint->decimal('intesa_fee', '10', '2')->default(.0)->after('cash_fee_type');
            $blueprint->enum('intesa_fee_type', ['fix', 'percent'])->default('fix')->after('intesa_fee');

            $blueprint->tinyInteger('active')->default(1)->after('to_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('frontend_domain_rules', function (Blueprint $blueprint) {
            $blueprint->dropColumn('agency_fee');
            $blueprint->dropColumn('agency_fee_type');
            $blueprint->dropColumn('brand_fee');
            $blueprint->dropColumn('brand_fee_type');
            $blueprint->dropColumn('cash_fee');
            $blueprint->dropColumn('cash_fee_type');
            $blueprint->dropColumn('intesa_fee');
            $blueprint->dropColumn('intese_fee_type');

            $blueprint->dropColumn('active');
        });
    }
}