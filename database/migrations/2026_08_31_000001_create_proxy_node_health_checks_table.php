<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_node_health_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proxy_node_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->boolean('successful');
            $table->unsignedInteger('latency_ms');
            $table->string('version', 64)->nullable();
            $table->string('error_code', 64)->nullable();

            $table->index(['proxy_node_id', 'checked_at']);
            $table->index(['checked_at', 'successful']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_node_health_checks');
    }
};
