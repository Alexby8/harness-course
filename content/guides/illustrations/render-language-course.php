<?php

declare(strict_types=1);

// Authoring helper: executes only the two dictionaries and pure lesson catalogue.
$output = $argv[1] ?? throw new RuntimeException('Pass an output directory');
if (!is_dir($output) && !mkdir($output, 0777, true)) {
    throw new RuntimeException('Cannot create output directory');
}
function block(string $slug, string $label): string
{
    $root = dirname(__DIR__);
    $package = $root.'/packages/'.$slug.'.json';
    $guides = json_decode(file_get_contents(is_file($package) ? $package : $root.'/current.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($guides as $guide) {
        if ($guide['slug'] !== $slug) { continue; }
        foreach ($guide['sections'] as $section) {
            foreach ($section['content']['content'] as $node) {
                $config = $node['attrs']['config'] ?? [];
                if (($node['attrs']['id'] ?? '') === 'guide-code' && ($config['label'] ?? '') === $label) {
                    return $config['code'];
                }
            }
        }
    }
    throw new RuntimeException('Missing block '.$label);
}
$fixture = [];
foreach (['ru', 'en'] as $locale) {
    $file = $output.'/dictionary-'.$locale.'.php';
    file_put_contents($file, block('telegram-bot-multiple-languages', 'PHP · lang/'.$locale.'/bot.php'));
    $fixture[$locale] = require $file;
}
$file = $output.'/Lessons.php';
file_put_contents($file, block('telegram-bot-mini-course', 'PHP · app/GuideBot/Lessons.php'));
require $file;
$fixture['lessons'] = App\GuideBot\Lessons::all();
file_put_contents($output.'/language-course.json', json_encode($fixture, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Dictionaries and lesson catalogue rendered without network calls\n";
