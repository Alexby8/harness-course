<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Guide;
use App\Services\Guides\GuideContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;

final class ImportGuidesCommand extends Command
{
    protected $signature = 'guides:import {file : JSON file containing an array of articles} {--dry-run : Validate without saving}';

    protected $description = 'Create or update guides by slug; omitted guides are never deleted';

    public function handle(GuideContent $content): int
    {
        $path = $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error('Cannot read the import file.');

            return self::FAILURE;
        }
        try {
            $json = file_get_contents($path);
            $items = json_decode($json === false ? '' : $json, true, 64, JSON_THROW_ON_ERROR);
            if (! is_array($items) || ! array_is_list($items) || $items === []) {
                throw new JsonException('Expected a non-empty array of guides.');
            }
            $seen = [];
            $changed = 0;
            DB::transaction(function () use ($items, $content, &$seen, &$changed): void {
                foreach ($items as $item) {
                    if (! is_array($item) || ! is_string($item['slug'] ?? null)) {
                        throw new JsonException('Every guide must have a slug.');
                    }
                    $slug = $item['slug'];
                    if (isset($seen[$slug])) {
                        throw new JsonException('Duplicate slug in import: '.$slug);
                    }
                    $seen[$slug] = true;
                    $guide = Guide::query()->where('slug', $slug)->first() ?? new Guide;
                    $guide->fill($content->validate($item, $guide->exists ? $guide->id : null));
                    if (! $guide->exists || $guide->isDirty()) {
                        $changed++;
                        if (! $this->option('dry-run')) {
                            $guide->save();
                        }
                    }
                }
            });
            $this->info(($this->option('dry-run') ? 'Validated' : 'Imported').': '.count($items).'; changed: '.$changed.'.');

            return self::SUCCESS;
        } catch (JsonException|ValidationException $exception) {
            $this->error($exception->getMessage());
            if ($exception instanceof ValidationException) {
                foreach ($exception->errors() as $field => $errors) {
                    $this->error($field.': '.implode(' ', $errors));
                }
            }

            return self::FAILURE;
        }
    }
}
