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
        Schema::create('user_social_media', function (Blueprint $table) {
            $table->id();
        
            $table->unsignedBigInteger('user_id');
            $table->string('type'); // Instagram, Facebook, etc.
            $table->text('url');
        
            $table->boolean('is_approved')->default(false);
            $table->unsignedBigInteger('approved_by_admin_id')->nullable();
            $table->dateTime('approved_on')->nullable();
        
            $table->timestamps();
        
            $table->foreign('user_id')->references('id')->on('users');
        
            // Indexes 🔥
            $table->index('user_id');
            $table->index('type');
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_social_media');
    }
};
