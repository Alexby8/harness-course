<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_daily_stats', function (Blueprint $table): void {
            // NULL preserves the uncertainty of historical successful API requests.
            $table->unsignedInteger('work_api_requests_count')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bot_daily_stats', function (Blueprint $table): void {
            $table->dropColumn('work_api_requests_count');
        });
    }
};
