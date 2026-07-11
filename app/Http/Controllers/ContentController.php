<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentItem;
use App\Models\TransitionTemplate;
use App\Services\ContentReviewService;
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
        $approvalStatus = $request->string('approval_status')->toString();

        $contentsQuery = ContentItem::query()
            ->visibleTo($this->user())
            ->with(['category', 'scenes', 'reviewer'])
            ->withCount('scenes')
            ->when($selectedUserIds !== [], fn ($query) => $query->whereIn('created_by', $selectedUserIds))
            ->when($fromDate, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
            ->when($approvalStatus, fn ($query) => $query->where('approval_status', $approvalStatus))
            ->orderByDesc('created_at');

        return view('contents.index', [
            'categories' => Category::orderBy('name')->get(),
            'contents' => $contentsQuery->get(),
            'users' => $this->user()->isAdmin() ? \App\Models\User::query()->orderBy('full_name')->get() : collect([$this->user()]),
            'selectedUserIds' => $selectedUserIds,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'approvalStatus' => $approvalStatus,
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
            'approval_status' => 'draft',
            'created_by' => $this->user()->id,
            'created_by_name' => $this->user()->display_name,
        ]);

        return redirect()->route('contents.show', $content)->with('status', 'Tạo content thành công.');
    }

    public function show(ContentItem $content)
    {
        $this->authorizeContentView($content);

        $content->load([
            'category',
            'scenes',
            'mainScenes.nextTransitionTemplate',
            'reviewHistories',
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
        $this->authorizeContentEdit($content);

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
        if (! $this->user()->isAdmin()) {
            $this->authorizeContentEdit($content);
        }

        $content->delete();

        return redirect()->route('contents.index')->with('status', 'Đã xóa content.');
    }

    public function submitReview(ContentItem $content, ContentReviewService $contentReviewService): RedirectResponse
    {
        $this->authorizeContentEdit($content);
        abort_unless(in_array($content->approval_status, ['draft', 'needs_revision'], true), 403);

        $contentReviewService->submitForReview($content, $this->user());

        return redirect()->route('contents.show', $content)->with('status', 'Đã gửi duyệt content.');
    }

    public function review(Request $request, ContentItem $content, ContentReviewService $contentReviewService): RedirectResponse
    {
        $this->authorizeContentReview($content);

        $allowedStatuses = $this->user()->isAdmin()
            ? ['draft', 'pending_review', 'needs_revision', 'approved', 'completed']
            : ['needs_revision', 'approved'];

        $validated = $request->validate([
            'approval_status' => ['required', 'in:'.implode(',', $allowedStatuses)],
            'review_comment' => ['nullable', 'string'],
        ]);

        $contentReviewService->updateReview(
            $content,
            $this->user(),
            $validated['approval_status'],
            $validated['review_comment'] ?? null,
        );

        return redirect()->route('contents.show', $content)->with('status', 'Đã cập nhật kết quả duyệt content.');
    }
}
