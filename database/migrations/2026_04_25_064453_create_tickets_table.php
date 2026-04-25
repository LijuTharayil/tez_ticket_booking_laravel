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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->integer('quantity'); // renamed from number_of_seats
            $table->dateTime('match_time');
            $table->string('name');
            $table->string('match_title');
            $table->text('match_details')->nullable();
            $table->string('venue')->nullable();
            $table->string('stadium')->nullable();
            $table->string('image')->nullable(); // added image
            $table->unsignedBigInteger('added_by_admin_id')->nullable();
            $table->unsignedBigInteger('last_updated_by_admin_id')->nullable();
            $table->decimal('ticket_rate_in_coin_quantity', 30, 8)->default(0);
            $table->timestamps();
        
            $table->index('ticket_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};
