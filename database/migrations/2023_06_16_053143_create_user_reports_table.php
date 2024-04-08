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
        Schema::create('user_reports', function (Blueprint $table) {
            $table->id();
            $table->text('description');
            $table->unsignedBigInteger('reported_by')->unsigned()->nullable();
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('reported_user')->unsigned()->nullable();
            $table->foreign('reported_user')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_reports');
    }
};
