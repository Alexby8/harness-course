<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name')->nullable()->change();
            $table->enum('status', ['active', 'disabled'])->default('active')->after('email');
            $table->string('api_key_hash', 64)->nullable()->unique()->after('status');
            $table->string('api_key_preview', 30)->nullable()->after('api_key_hash');
            $table->boolean('is_admin')->default(false)->after('api_key_preview');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['status', 'api_key_hash', 'api_key_preview', 'is_admin']);
            $table->string('name')->nullable(false)->change();
        });
    }
};
