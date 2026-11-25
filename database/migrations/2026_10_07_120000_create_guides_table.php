<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('seo_title')->nullable();
            $table->text('description');
            $table->string('related_title')->nullable();
            $table->text('card_description')->nullable();
            $table->string('category');
            $table->string('topics')->default('Telegram Bot API');
            $table->string('reading_time', 50);
            $table->date('published_at');
            $table->boolean('is_published')->default(true)->index();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('related_order')->default(0);
            $table->json('intro');
            $table->json('preamble');
            $table->json('sections');
            $table->timestamp('content_updated_at')->nullable();
            $table->timestamps();
        });

        // Immutable initial content snapshot; subsequent deployments never re-import it.
        $guides = json_decode(file_get_contents(database_path('data/guides.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($guides as $guide) {
            foreach (['intro', 'preamble', 'sections'] as $field) {
                $guide[$field] = json_encode($guide[$field], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            }
            $guide['created_at'] = $guide['updated_at'] = now();
            DB::table('guides')->insert($guide);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
