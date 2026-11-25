<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Guide;
use Illuminate\Console\Command;

final class ExportGuidesCommand extends Command
{
    protected $signature = 'guides:export {file : Destination JSON file} {--slug= : Export one guide}';

    protected $description = 'Export guides, including unpublished articles, without changing the database';

    public function handle(): int
    {
        $query = Guide::query()->orderBy('sort_order')->orderBy('id');
        $slug = $this->option('slug');
        if (is_string($slug) && $slug !== '') {
            $query->where('slug', $slug);
        }
        $guides = $query->get();
        if ($guides->isEmpty()) {
            $this->error('No guides found.');

            return self::FAILURE;
        }
        $json = json_encode($guides->map(fn (Guide $guide): array => $guide->export())->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $path = $this->argument('file');
        if (@file_put_contents($path, $json."\n") === false) {
            $this->error('Cannot write the export file.');

            return self::FAILURE;
        }
        $this->info('Exported: '.$guides->count().'.');

        return self::SUCCESS;
    }
}
