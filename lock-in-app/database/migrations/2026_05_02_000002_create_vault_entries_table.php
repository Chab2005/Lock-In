<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('website', 255)->nullable();
            $table->string('nickname', 255)->nullable();
            $table->string('email_hint', 255)->nullable();
            $table->text('encrypted_password');
            $table->string('iv', 24);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_entries');
    }
};
