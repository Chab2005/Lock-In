<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vault_entries', function (Blueprint $table) {
            $table->string('icon', 64)->nullable()->default('lock')->after('email_hint');
        });
    }

    public function down(): void
    {
        Schema::table('vault_entries', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
