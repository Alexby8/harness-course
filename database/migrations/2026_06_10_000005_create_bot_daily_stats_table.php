<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_daily_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('api_requests_count')->default(0);
            $table->unsignedInteger('api_errors_count')->default(0);
            $table->unsignedInteger('webhook_received_count')->default(0);
            $table->unsignedInteger('webhook_sent_count')->default(0);
            $table->unsignedInteger('webhook_failed_count')->default(0);
            $table->timestamps();

            $table->unique(['bot_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_daily_stats');
    }
};
