<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentItem;
use App\Models\TransitionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $selectedUserIds = $this->user()->isAdmin()
            ? collect($request->input('user_ids', []))->filter()->map(fn ($value) => (int) $value)->values()->all()
            : [$this->user()->id];
        $fromDate = $request->string('from_date')->toString();
        $toDate = $request->string('to_date')->toString();

        $contentsQuery = ContentItem::query()
            ->visibleTo($this->user())
            ->with(['category', 'scenes'])
            ->withCount('scenes')
            ->when($selectedUserIds !== [], fn ($query) => $query->whereIn('created_by', $selectedUserIds))
            ->when($fromDate, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
            ->orderByDesc('created_at');

        return view('contents.index', [
            'categories' => Category::orderBy('name')->get(),
            'contents' => $contentsQuery->get(),
            'users' => $this->user()->isAdmin() ? \App\Models\User::query()->orderBy('full_name')->get() : collect([$this->user()]),
            'selectedUserIds' => $selectedUserIds,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $content = ContentItem::create([
            ...$validated,
            'created_by' => $this->user()->id,
            'created_by_name' => $this->user()->display_name,
        ]);

        return redirect()->route('contents.show', $content)->with('status', 'Tạo content thành công.');
    }

    public function show(ContentItem $content)
    {
        $this->authorizeOwnership($content);

        $content->load([
            'category',
            'scenes',
            'mainScenes.nextTransitionTemplate',
        ]);

        $previewSequence = $content->scenes
            ->sortBy([
                ['sort_order', 'asc'],
                ['position', 'asc'],
            ])
            ->values();

        return view('contents.show', [
            'content' => $content,
            'categories' => Category::orderBy('name')->get(),
            'previewSequence' => $previewSequence,
            'transitionTemplates' => TransitionTemplate::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ContentItem $content): RedirectResponse
    {
        $this->authorizeOwnership($content);

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
        $this->authorizeOwnership($content);

        $content->delete();

        return redirect()->route('contents.index')->with('status', 'Đã xóa content.');
    }
}
