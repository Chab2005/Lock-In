<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email', 255);
            $table->string('label', 255)->nullable();
            $table->text('encrypted_payload');
            $table->string('iv', 24);
            $table->string('share_token_hash', 64);
            $table->string('status', 16)->default('active');
            $table->timestamps();

            $table->index(['recipient_email', 'status']);
            $table->index(['owner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_entries');
    }
};
