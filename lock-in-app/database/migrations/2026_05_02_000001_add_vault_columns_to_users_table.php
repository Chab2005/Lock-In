<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vault_salt', 64)->nullable()->after('remember_token');
            $table->string('vault_kdf_params', 512)->nullable()->after('vault_salt');
            $table->text('vault_verifier')->nullable()->after('vault_kdf_params');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vault_salt', 'vault_kdf_params', 'vault_verifier']);
        });
    }
};
