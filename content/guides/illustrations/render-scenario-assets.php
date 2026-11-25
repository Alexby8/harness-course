<?php

declare(strict_types=1);

// Local illustration helper. Executes only the two self-contained, reviewed
// article functions, with an in-memory database and invented catalog data.
$output = $argv[1] ?? throw new InvalidArgumentException('Output directory required');
if (! is_dir($output) && ! mkdir($output, 0777, true)) {
    throw new RuntimeException('Cannot create output directory');
}
$output = realpath($output);

function articleCode(string $slug, string $label): string
{
    $package = __DIR__.'/../packages/'.$slug.'.json';
    $source = is_file($package) ? $package : __DIR__.'/../current.json';
    foreach (json_decode(file_get_contents($source), true, flags: JSON_THROW_ON_ERROR) as $article) {
        if ($article['slug'] !== $slug) {
            continue;
        }
        foreach ($article['sections'] as $section) {
            foreach ($section['content']['content'] as $node) {
                if (($node['attrs']['id'] ?? null) === 'guide-code'
                    && ($node['attrs']['config']['label'] ?? null) === $label) {
                    return $node['attrs']['config']['code'];
                }
            }
        }
    }
    throw new RuntimeException('Missing article source: '.$label);
}

foreach ([
    ['telegram-inline-bot-product-search', 'PHP · inline-products.php', 'inline-products.php'],
    ['telegram-bot-product-catalog-pagination', 'PHP · catalog-page.php', 'catalog-page.php'],
] as [$slug, $label, $filename]) {
    file_put_contents($output.'/'.$filename, articleCode($slug, $label));
    require $output.'/'.$filename;
}

$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE products (id INTEGER PRIMARY KEY, category_id INTEGER, title TEXT, price_minor INTEGER, is_active INTEGER, sort_order INTEGER)');
$insert = $db->prepare('INSERT INTO products VALUES (?, 1, ?, ?, 1, ?)');
foreach ([['Блокнот', 39000], ['Ручка', 9000], ['Карандаш', 5000], ['Папка', 16000], ['Ластик', 4000], ['Линейка', 8000]] as $index => [$title, $price]) {
    $insert->execute([$index + 1, $title, $price, $index]);
}

$fixtures = [
    'inline' => inlineProducts(['id' => 'illustration-demo', 'query' => 'лампа', 'offset' => '']),
    'catalogFirst' => catalogPage($db, 1, 0, 'demomenu', 1),
    'catalogLast' => catalogPage($db, 1, 1, 'demomenu', 2),
];
file_put_contents($output.'/scenario-fixtures.json', json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
echo "Prepared article output with demo data; no network calls.\n";
