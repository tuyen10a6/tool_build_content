<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;

class PreviewController extends Controller
{
    public function index()
    {
        return view('preview.index', [
            'contents' => ContentItem::query()
                ->visibleTo($this->user())
                ->with(['category', 'scenes' => fn ($query) => $query->orderBy('sort_order')->orderBy('position')])
                ->withCount('scenes')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }
}
