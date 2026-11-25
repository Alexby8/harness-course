<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class DocsController extends Controller
{
    public function index(): View
    {
        $baseUrl = rtrim(config('app.url'), '/') . '/api';

        return view('docs.index', compact('baseUrl'));
    }
}
