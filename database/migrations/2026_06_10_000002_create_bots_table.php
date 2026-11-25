<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('public_id', 30)->unique();
            $table->string('name');
            $table->text('telegram_token_encrypted');
            $table->string('telegram_token_hash', 64)->unique();
            $table->string('telegram_bot_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('telegram_first_name')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret', 64)->nullable();
            $table->boolean('webhook_enabled')->default(false);
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamp('last_webhook_delivery_at')->nullable();
            $table->text('last_webhook_error')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
