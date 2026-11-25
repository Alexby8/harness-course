<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Not unique: historical duplicates stay. Receiver serializes on bots.id.
        Schema::table('webhook_events', function (Blueprint $table): void {
            $table->timestamp('queued_at')->nullable();
            $table->index(['bot_id', 'telegram_update_id'], 'webhook_events_bot_update_index');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table): void {
            $table->dropIndex('webhook_events_bot_update_index');
            $table->dropColumn('queued_at');
        });
    }
};
