<?php

declare(strict_types=1);
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Blade;

// Local authoring helper. Dependencies belong to an isolated tools directory,
// not to the application or its deployment. See content/guides/README.md.
$output = realpath($argv[1] ?? '');
$autoload = realpath($argv[2] ?? '');
if ($output === false || ! is_dir($output) || $autoload === false || ! is_file($autoload)) {
    throw new InvalidArgumentException('Usage: php render-practical-assets.php OUTPUT_DIRECTORY TOOLS_AUTOLOAD');
}

require $autoload;
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Both files are extracted from the trusted, local article package by the builder.
require $output.'/pdf-fixture.php';
$template = file_get_contents($output.'/pdf-template.blade.php');
if ($template === false || ! isset($snapshot) || ! is_array($snapshot)) {
    throw new RuntimeException('Run build-practical-illustrations.mjs first.');
}
$html = Blade::render($template, $snapshot);
$pdf = new Dompdf\Dompdf(['isRemoteEnabled' => false, 'fontCache' => $output.'/tools/font-cache']);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('a4');
$pdf->render();
file_put_contents($output.'/application-confirmation.pdf', $pdf->output());

// Deliberately public fixture: this token is not registered in any ticket database.
$token = str_repeat('a', 32).'.'.hash_hmac('sha256', str_repeat('a', 32), 'illustration-only-not-a-real-ticket-key');
$qr = new QrCode(data: $token, size: 320, margin: 16);
(new PngWriter)->write($qr)->saveToFile($output.'/ticket-demo.png');
echo "PDF and demonstration QR rendered\n";
