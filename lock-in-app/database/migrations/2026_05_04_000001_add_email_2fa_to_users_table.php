<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_2fa_email_enabled')->default(false)->after('remember_token');
            $table->string('email_otp_hash', 64)->nullable()->after('is_2fa_email_enabled');
            $table->timestamp('email_otp_expires_at')->nullable()->after('email_otp_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_2fa_email_enabled', 'email_otp_hash', 'email_otp_expires_at']);
        });
    }
};
