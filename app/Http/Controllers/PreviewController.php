<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;

class PreviewController extends Controller
{
    public function index()
    {
        return view('preview.index', [
            'contents' => ContentItem::with(['category', 'scenes'])->withCount('scenes')->get(),
        ]);
    }
}
