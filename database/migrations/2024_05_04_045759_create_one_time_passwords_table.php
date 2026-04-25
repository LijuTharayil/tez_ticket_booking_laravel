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
        Schema::create('one_time_passwords', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('type')->nullable();
            $table->string('medium')->nullable();
            $table->integer('otp_number')->nullable();
            $table->boolean('status')->default(true);
            $table->string('token')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'otp_number', 'medium', 'status']);
            $table->index(['user_id', 'medium', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('one_time_passwords');
    }
};
