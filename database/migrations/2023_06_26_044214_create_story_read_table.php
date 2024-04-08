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
        Schema::create('story_read', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('story_id')->unsigned();
            $table->unsignedBigInteger('read_by')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
            $table->foreign('read_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_read');
    }
};
