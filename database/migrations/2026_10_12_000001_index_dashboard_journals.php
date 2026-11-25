<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_request_logs', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at', 'id'], 'api_logs_user_created_index');
        });

        Schema::table('webhook_events', function (Blueprint $table): void {
            $table->index(['bot_id', 'created_at', 'id'], 'webhook_events_bot_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('api_request_logs', function (Blueprint $table): void {
            $table->dropIndex('api_logs_user_created_index');
        });

        Schema::table('webhook_events', function (Blueprint $table): void {
            $table->dropIndex('webhook_events_bot_created_index');
        });
    }
};
