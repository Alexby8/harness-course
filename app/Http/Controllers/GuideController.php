<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Services\Guides\GuideContent;
use App\Services\Guides\GuideViewCounter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class GuideController extends Controller
{
    public function index(Request $request): Response
    {
        $page = filter_var($request->query('page', '1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($page === false, 404);

        $guides = Guide::query()->published()->orderBy('sort_order')->orderBy('id')->paginate(12, [
            'id', 'slug', 'title', 'seo_title', 'description', 'card_description',
            'category', 'topics', 'reading_time', 'published_at',
        ], 'page', $page)->withPath('/guides')->onEachSide(1);
        abort_if($page > $guides->lastPage(), 404);

        return response()->view('guides.index', [
            'guides' => $guides,
            'canonicalPath' => $page === 1 ? '/guides' : '/guides?page='.$page,
        ]);
    }

    public function show(string $slug, GuideContent $content): Response
    {
        $guide = Guide::query()->published()->where('slug', $slug)->firstOrFail();

        return response()->view('guides.show', [
            'guide' => $guide,
            'article' => $guide->metadata(),
            'sections' => array_column($guide->sections, 'label', 'id'),
            'introHtml' => $content->render($guide->intro),
            'preambleHtml' => $content->render($guide->preamble),
            'bodySections' => array_map(fn (array $section): array => [
                'id' => $section['id'], 'html' => $content->render($section['content']),
            ], $guide->sections),
        ]);
    }

    public function recordView(Request $request, string $slug, GuideViewCounter $counter): Response
    {
        $guide = Guide::query()->published()->where('slug', $slug)->firstOrFail(['id']);
        $counter->record($guide->id, $request->session()->getId());

        return response()->noContent();
    }
}
