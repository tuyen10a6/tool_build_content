<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentItem;
use App\Models\TransitionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function index()
    {
        return view('contents.index', [
            'categories' => Category::orderBy('name')->get(),
            'contents' => ContentItem::with(['category', 'scenes'])->withCount('scenes')->get(),
        ]);
    }

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
        $content->load([
            'category',
            'scenes',
            'mainScenes.nextTransitionTemplate',
        ]);

        return view('contents.show', [
            'content' => $content,
            'categories' => Category::orderBy('name')->get(),
            'transitionTemplates' => TransitionTemplate::query()->where('is_active', true)->orderBy('name')->get(),
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

        return redirect()->route('contents.index')->with('status', 'Đã xóa content.');
    }
}
