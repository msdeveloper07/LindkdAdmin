<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_block', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blocked_by')->unsigned()->nullable();
            $table->foreign('blocked_by')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('blocked_user')->unsigned()->nullable();
            $table->foreign('blocked_user')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_block');
    }
};
