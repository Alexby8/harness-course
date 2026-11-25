<?php
declare(strict_types=1);
// Local authoring helper: execute the article's pure text formatter, no API/DB.
$output=$argv[1]??throw new RuntimeException('Pass output directory');
$file=__DIR__.'/../packages/telegram-daily-sales-report.json';
$items=json_decode(file_get_contents(is_file($file)?$file:__DIR__.'/../current.json'),true,512,JSON_THROW_ON_ERROR);
$source=null;
foreach($items as $article){
    if($article['slug']!=='telegram-daily-sales-report')continue;
    foreach($article['sections'] as $section)foreach($section['content']['content'] as $node){
        if(($node['attrs']['config']['label']??'')==='PHP · reporting.php')$source=$node['attrs']['config']['code'];
    }
}
if(!is_string($source))throw new RuntimeException('Report formatter missing');
eval(substr($source,5));
@mkdir($output,0777,true);
file_put_contents($output.'/report-fixture.json',json_encode(['text'=>reportText('2026-11-18','Europe/Moscow',
    ['paid'=>150000,'refunds'=>20000,'orders'=>1,'leads'=>2,'net'=>130000],
    ['paid'=>100000,'refunds'=>0,'orders'=>1,'leads'=>1,'net'=>100000])],JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
