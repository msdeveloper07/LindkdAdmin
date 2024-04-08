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
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp')->nullable()->after('email');
            $table->string('phone_number')->nullable()->after('otp');
            $table->enum('is_verify', ['0', '1'])->default('0')->after('phone_number');
            $table->enum('is_location_enable', ['0', '1'])->default('0')->after('is_verify');
            $table->string('profile_image')->nullable()->after('is_location_enable');
            $table->enum('chat_notification', ['0', '1'])->default('0')->after('profile_image');
            $table->enum('vanish_mode', ['0', '1'])->default('0')->after('chat_notification');
            $table->string('facebook_id')->nullable()->after('vanish_mode');
            $table->string('google_id')->nullable()->after('facebook_id');
            $table->enum('provider', ['app', 'google', 'facebook'])->default('app')->after('google_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('otp');
            $table->dropColumn('phone_number');
            $table->dropColumn('is_verify');
            $table->dropColumn('is_location_enable');
            $table->dropColumn('profile_image');
            $table->dropColumn('chat_notification');
            $table->dropColumn('vanish_mode');
            $table->dropColumn('facebook_id');
            $table->dropColumn('google_id');
            $table->dropColumn('provider');
        });
    }
};
