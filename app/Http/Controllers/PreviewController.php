<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;

class PreviewController extends Controller
{
    public function index()
    {
        return view('preview.index', [
            'contents' => ContentItem::with(['category', 'scenes' => fn ($query) => $query->orderBy('sort_order')->orderBy('position')])->withCount('scenes')->get(),
        ]);
    }
}
