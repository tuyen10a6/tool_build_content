<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentItem;

class DashboardController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('contents')->with([
            'contents' => fn ($query) => $query->withCount('scenes')->with('category'),
        ])->get();

        $contents = ContentItem::with(['category', 'scenes'])->withCount('scenes')->get();

        return view('dashboard', [
            'categories' => $categories,
            'contents' => $contents,
        ]);
    }
}
