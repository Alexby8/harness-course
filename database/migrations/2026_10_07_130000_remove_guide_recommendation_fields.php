<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guides', function (Blueprint $table): void {
            $table->dropColumn(['is_featured', 'related_order', 'related_title']);
        });
    }

    public function down(): void
    {
        Schema::table('guides', function (Blueprint $table): void {
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('related_order')->default(0);
            $table->string('related_title')->nullable();
        });
    }
};
