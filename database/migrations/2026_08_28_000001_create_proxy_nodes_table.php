<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_nodes', function (Blueprint $table): void {
            $table->id();
            $table->string('node_id', 64)->unique();
            $table->string('name');
            $table->string('url', 500)->unique();
            $table->text('secret_encrypted');
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('enabled')->default(false);
            $table->enum('status', ['unknown', 'online', 'offline'])->default('unknown');
            $table->enum('telegram_status', ['unknown', 'available', 'unavailable'])->default('unknown');
            $table->timestamp('last_check_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('version', 64)->nullable();
            $table->timestamps();

            $table->index(['enabled', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_nodes');
    }
};
