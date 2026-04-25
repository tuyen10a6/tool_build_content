<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $content = ContentItem::create($validated);

        return redirect()->route('contents.show', $content)->with('status', 'Tạo content thành công.');
    }

    public function show(ContentItem $content)
    {
        $content->load(['category', 'scenes']);

        return view('contents.show', [
            'content' => $content,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ContentItem $content): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $content->update($validated);

        return redirect()->route('contents.show', $content)->with('status', 'Cập nhật content thành công.');
    }

    public function destroy(ContentItem $content): RedirectResponse
    {
        $content->delete();

        return redirect()->route('dashboard')->with('status', 'Đã xóa content.');
    }
}
