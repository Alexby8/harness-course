<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertising_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('channel', 32);
            $table->string('external_id', 64)->nullable();
            $table->string('utm_source', 64);
            $table->string('utm_medium', 32);
            $table->string('utm_campaign', 96);
            $table->char('match_key', 64)->unique();
            $table->date('started_on')->nullable();
            $table->date('stopped_on')->nullable();
            $table->boolean('includes_vat')->default(true);
            $table->boolean('archived')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('advertising_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advertising_campaign_id')->constrained()->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedBigInteger('clicks')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['advertising_campaign_id', 'period_start', 'period_end'], 'advertising_expense_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertising_expenses');
        Schema::dropIfExists('advertising_campaigns');
    }
};
