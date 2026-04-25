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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('transaction_id')->unique();
            $table->string('type');
            $table->string('account_type'); // Credit / Debit
            $table->decimal('token_quantity', 30, 10)->default(0);
            $table->dateTime('transaction_on');
            $table->string('reference_id')->nullable();
            $table->string('reference_model')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type', 'account_type']);
            $table->index(['user_id', 'transaction_on']);
            $table->index(['user_id', 'type']);
            $table->index('reference_id');
            $table->index('reference_model');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
