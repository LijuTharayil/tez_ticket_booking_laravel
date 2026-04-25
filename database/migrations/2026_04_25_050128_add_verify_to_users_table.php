<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_email_verified')->default(false)->after('status');
            $table->boolean('is_mobile_verified')->default(false)->after('is_email_verified');

            $table->decimal('total_token_credit', 30, 10)->default(0)->after('wallet_address');
            $table->decimal('total_token_debit', 30, 10)->default(0)->after('total_token_credit');
            $table->decimal('total_token_balance', 30, 10)->default(0)->after('total_token_debit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
           $table->dropColumn('is_email_verified', 'is_mobile_verified', 'total_token_credit', 'total_token_debit', 'total_token_balance');
        });
    }
};
