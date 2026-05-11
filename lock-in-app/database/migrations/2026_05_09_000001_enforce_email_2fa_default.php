<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change the column default so every future user gets email OTP on.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_2fa_email_enabled')->default(true)->change();
        });

        // Back-fill existing accounts that have neither email OTP nor a confirmed
        // TOTP secret — they would otherwise have no MFA method at all.
        DB::table('users')
            ->where('is_2fa_email_enabled', false)
            ->whereNull('two_factor_confirmed_at')
            ->update(['is_2fa_email_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_2fa_email_enabled')->default(false)->change();
        });
    }
};
