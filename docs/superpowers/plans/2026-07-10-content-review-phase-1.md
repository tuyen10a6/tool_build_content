# Content Review Phase 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bổ sung nền tảng dữ liệu, quyền `reviewer`, và workflow duyệt content cốt lõi để hệ thống có thể quản lý trạng thái content và phân quyền review đúng nghiệp vụ.

**Architecture:** Phase 1 sẽ thêm các cột workflow vào `content_items`, tạo bảng `content_review_histories`, mở rộng role `reviewer`, và tách quyền backend theo action thay vì chỉ dùng ownership/admin đơn giản. Controller hiện có sẽ được chỉnh tối thiểu để hỗ trợ status, nhận xét và chặn sửa/xuất sai quyền, chưa làm email và chưa refactor UI lớn sang table đầy đủ.

**Tech Stack:** Laravel, Eloquent models, migrations, middleware/controller authorization helpers, feature tests với PHPUnit.

## Global Constraints

- Không trust frontend đối với `approval_status`, `reviewed_by`, `reviewed_by_name`, `reviewed_at`, `revision_requested_count`, `role`.
- Reviewer chỉ được duyệt, nhận xét, preview; không được sửa nội dung content/scene/media.
- User chỉ được sửa content/scene của mình khi status là `Mới` hoặc `Cần sửa`.
- Export folder chỉ admin.
- Mọi thay đổi trạng thái content phải ghi vào bảng lịch sử kiểm duyệt.

---

### Task 1: Add review workflow persistence

**Files:**
- Create: `database/migrations/2026_07_10_000000_add_review_workflow_to_content_items_table.php`
- Create: `database/migrations/2026_07_10_000100_create_content_review_histories_table.php`
- Modify: `app/Models/ContentItem.php`
- Create: `app/Models/ContentReviewHistory.php`
- Test: `tests/Feature/ContentReviewWorkflowTest.php`

**Interfaces:**
- Consumes: Existing `content_items` table and `ContentItem` model.
- Produces:
  - `content_items.approval_status: string`
  - `content_items.review_comment: text|null`
  - `content_items.reviewed_by: bigint|null`
  - `content_items.reviewed_by_name: string|null`
  - `content_items.reviewed_at: datetime|null`
  - `content_items.submitted_at: datetime|null`
  - `content_items.revision_requested_count: unsignedInteger`
  - `App\Models\ContentReviewHistory`
  - `ContentItem::reviewHistories(): HasMany`

- [ ] **Step 1: Write the failing persistence test**

```php
public function test_content_review_workflow_columns_and_history_model_are_available(): void
{
    $content = ContentItem::factory()->create();

    $content->update([
        'approval_status' => 'draft',
        'review_comment' => 'Need revision',
        'reviewed_by' => 10,
        'reviewed_by_name' => 'Reviewer A',
        'revision_requested_count' => 1,
    ]);

    $history = $content->reviewHistories()->create([
        'from_status' => 'pending_review',
        'to_status' => 'needs_revision',
        'comment' => 'Need revision',
        'acted_by' => 10,
        'acted_by_name' => 'Reviewer A',
        'acted_role' => 'reviewer',
    ]);

    $this->assertSame('draft', $content->fresh()->approval_status);
    $this->assertSame('needs_revision', $history->to_status);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/ContentReviewWorkflowTest.php --filter=columns_and_history`

Expected: FAIL because the new columns and model relationship do not exist yet.

- [ ] **Step 3: Write the minimal schema and model implementation**

```php
Schema::table('content_items', function (Blueprint $table) {
    $table->string('approval_status')->default('draft')->after('description');
    $table->text('review_comment')->nullable()->after('approval_status');
    $table->foreignId('reviewed_by')->nullable()->after('review_comment')->constrained('users')->nullOnDelete();
    $table->string('reviewed_by_name')->nullable()->after('reviewed_by');
    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_name');
    $table->timestamp('submitted_at')->nullable()->after('reviewed_at');
    $table->unsignedInteger('revision_requested_count')->default(0)->after('submitted_at');
});
```

```php
class ContentReviewHistory extends Model
{
    protected $fillable = [
        'content_item_id',
        'from_status',
        'to_status',
        'comment',
        'acted_by',
        'acted_by_name',
        'acted_role',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }
}
```

```php
public function reviewHistories(): HasMany
{
    return $this->hasMany(ContentReviewHistory::class)->latest();
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/ContentReviewWorkflowTest.php --filter=columns_and_history`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_10_000000_add_review_workflow_to_content_items_table.php database/migrations/2026_07_10_000100_create_content_review_histories_table.php app/Models/ContentItem.php app/Models/ContentReviewHistory.php tests/Feature/ContentReviewWorkflowTest.php
git commit -m "feat: add content review workflow persistence"
```

### Task 2: Extend users and role helpers for reviewer flow

**Files:**
- Create: `database/migrations/2026_07_10_000200_normalize_users_for_reviewer_role.php`
- Modify: `app/Models/User.php`
- Modify: `app/Http/Controllers/UserController.php`
- Modify: `resources/views/users/index.blade.php`
- Test: `tests/Feature/UserReviewerManagementTest.php`

**Interfaces:**
- Consumes: Existing `users` table, `UserController::validatePayload()`.
- Produces:
  - `User::isReviewer(): bool`
  - `User::canReviewContent(): bool`
  - user forms support `role=reviewer`
  - user forms use explicit `email`

- [ ] **Step 1: Write the failing reviewer management test**

```php
public function test_admin_can_create_reviewer_with_email(): void
{
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'username' => 'reviewer1',
        'full_name' => 'Reviewer One',
        'email' => 'reviewer1@example.com',
        'phone' => '0900000001',
        'note' => 'QA reviewer',
        'role' => 'reviewer',
        'status' => 'active',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', [
        'username' => 'reviewer1',
        'email' => 'reviewer1@example.com',
        'role' => 'reviewer',
    ]);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/UserReviewerManagementTest.php --filter=create_reviewer`

Expected: FAIL because `reviewer` and explicit email are not allowed yet.

- [ ] **Step 3: Write the minimal user role implementation**

```php
public function isReviewer(): bool
{
    return $this->role === 'reviewer';
}

public function canReviewContent(): bool
{
    return $this->isAdmin() || $this->isReviewer();
}
```

```php
'email' => [
    'required',
    'email',
    'max:255',
    Rule::unique('users', 'email')->ignore($user?->id),
],
'role' => ['required', Rule::in(['admin', 'reviewer', 'user'])],
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/UserReviewerManagementTest.php --filter=create_reviewer`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_10_000200_normalize_users_for_reviewer_role.php app/Models/User.php app/Http/Controllers/UserController.php resources/views/users/index.blade.php tests/Feature/UserReviewerManagementTest.php
git commit -m "feat: add reviewer role and explicit email support"
```

### Task 3: Replace simple ownership checks with content review authorization rules

**Files:**
- Modify: `app/Http/Controllers/Controller.php`
- Modify: `app/Models/ContentItem.php`
- Modify: `app/Models/Scene.php`
- Test: `tests/Feature/ContentReviewAuthorizationTest.php`

**Interfaces:**
- Consumes: `User::isAdmin()`, `User::isReviewer()`, `ContentItem.approval_status`, `Scene->content`.
- Produces:
  - `Controller::authorizeContentView(ContentItem $content): void`
  - `Controller::authorizeContentEdit(ContentItem $content): void`
  - `Controller::authorizeContentReview(ContentItem $content): void`
  - `Controller::authorizeContentExport(ContentItem $content): void`
  - `Controller::authorizeSceneEdit(Scene $scene): void`

- [ ] **Step 1: Write the failing authorization test**

```php
public function test_reviewer_can_view_but_cannot_edit_or_export_content(): void
{
    $reviewer = User::factory()->create(['role' => 'reviewer']);
    $owner = User::factory()->create(['role' => 'user']);
    $content = ContentItem::factory()->create([
        'created_by' => $owner->id,
        'created_by_name' => $owner->display_name,
        'approval_status' => 'pending_review',
    ]);

    $this->actingAs($reviewer)->get(route('contents.show', $content))->assertOk();
    $this->actingAs($reviewer)->put(route('contents.update', $content), [
        'category_id' => $content->category_id,
        'name' => 'Changed',
        'description' => 'Changed',
    ])->assertForbidden();
    $this->actingAs($reviewer)->get(route('exports.contents', $content))->assertForbidden();
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/ContentReviewAuthorizationTest.php --filter=reviewer_can_view_but_cannot_edit_or_export_content`

Expected: FAIL because reviewer access rules are not implemented yet.

- [ ] **Step 3: Write the minimal authorization helpers**

```php
protected function authorizeContentView(ContentItem $content): void
{
    if ($this->user()->isAdmin() || $this->user()->isReviewer()) {
        return;
    }

    abort_unless((int) $content->created_by === (int) $this->user()->id, Response::HTTP_FORBIDDEN);
}
```

```php
protected function authorizeContentEdit(ContentItem $content): void
{
    if ($this->user()->isAdmin()) {
        return;
    }

    abort_unless(
        $this->user()->role === 'user'
        && (int) $content->created_by === (int) $this->user()->id
        && in_array($content->approval_status, ['draft', 'needs_revision'], true),
        Response::HTTP_FORBIDDEN
    );
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/ContentReviewAuthorizationTest.php --filter=reviewer_can_view_but_cannot_edit_or_export_content`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Controller.php app/Models/ContentItem.php app/Models/Scene.php tests/Feature/ContentReviewAuthorizationTest.php
git commit -m "feat: add content review authorization rules"
```

### Task 4: Implement content review state transitions and history logging

**Files:**
- Modify: `app/Http/Controllers/ContentController.php`
- Create: `app/Services/ContentReviewService.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ContentReviewWorkflowTest.php`

**Interfaces:**
- Consumes:
  - `ContentReviewHistory`
  - `Controller::authorizeContentReview()`
  - `Controller::authorizeContentEdit()`
- Produces:
  - `POST /contents/{content}/submit-review`
  - `POST /contents/{content}/review`
  - `ContentReviewService::submitForReview(ContentItem $content, User $actor): void`
  - `ContentReviewService::updateReview(ContentItem $content, User $actor, string $toStatus, ?string $comment): void`

- [ ] **Step 1: Write the failing workflow transition tests**

```php
public function test_user_can_submit_owned_content_for_review(): void
{
    $user = User::factory()->create(['role' => 'user']);
    $content = ContentItem::factory()->create([
        'created_by' => $user->id,
        'created_by_name' => $user->display_name,
        'approval_status' => 'draft',
    ]);

    $response = $this->actingAs($user)->post(route('contents.submit-review', $content));

    $response->assertRedirect(route('contents.show', $content));
    $this->assertDatabaseHas('content_items', [
        'id' => $content->id,
        'approval_status' => 'pending_review',
    ]);
    $this->assertDatabaseHas('content_review_histories', [
        'content_item_id' => $content->id,
        'from_status' => 'draft',
        'to_status' => 'pending_review',
        'acted_role' => 'user',
    ]);
}
```

```php
public function test_reviewer_can_mark_pending_content_as_needs_revision(): void
{
    $reviewer = User::factory()->create(['role' => 'reviewer']);
    $owner = User::factory()->create(['role' => 'user']);
    $content = ContentItem::factory()->create([
        'created_by' => $owner->id,
        'created_by_name' => $owner->display_name,
        'approval_status' => 'pending_review',
    ]);

    $response = $this->actingAs($reviewer)->post(route('contents.review', $content), [
        'approval_status' => 'needs_revision',
        'review_comment' => 'Please revise scene 2',
    ]);

    $response->assertRedirect(route('contents.show', $content));
    $this->assertDatabaseHas('content_items', [
        'id' => $content->id,
        'approval_status' => 'needs_revision',
        'review_comment' => 'Please revise scene 2',
        'revision_requested_count' => 1,
    ]);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/ContentReviewWorkflowTest.php`

Expected: FAIL because review routes and transitions do not exist yet.

- [ ] **Step 3: Write the minimal workflow implementation**

```php
Route::post('/contents/{content}/submit-review', [ContentController::class, 'submitReview'])->name('contents.submit-review');
Route::post('/contents/{content}/review', [ContentController::class, 'review'])->name('contents.review');
```

```php
public function submitForReview(ContentItem $content, User $actor): void
{
    $fromStatus = $content->approval_status;

    $content->update([
        'approval_status' => 'pending_review',
        'submitted_at' => now(),
    ]);

    $content->reviewHistories()->create([
        'from_status' => $fromStatus,
        'to_status' => 'pending_review',
        'comment' => null,
        'acted_by' => $actor->id,
        'acted_by_name' => $actor->display_name,
        'acted_role' => $actor->role,
    ]);
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/ContentReviewWorkflowTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ContentController.php app/Services/ContentReviewService.php routes/web.php tests/Feature/ContentReviewWorkflowTest.php
git commit -m "feat: add content review workflow transitions"
```

### Task 5: Restrict export and preserve existing screens under Phase 1 rules

**Files:**
- Modify: `app/Http/Controllers/ExportController.php`
- Modify: `app/Http/Controllers/ContentController.php`
- Modify: `app/Http/Controllers/SceneController.php`
- Test: `tests/Feature/ContentReviewAuthorizationTest.php`

**Interfaces:**
- Consumes:
  - `Controller::authorizeContentExport()`
  - `Controller::authorizeContentEdit()`
  - `Controller::authorizeSceneEdit()`
- Produces:
  - export endpoints forbidden for reviewer/user
  - content/scene mutation endpoints respect review status

- [ ] **Step 1: Write the failing restriction test**

```php
public function test_user_cannot_edit_content_while_pending_review(): void
{
    $user = User::factory()->create(['role' => 'user']);
    $content = ContentItem::factory()->create([
        'created_by' => $user->id,
        'created_by_name' => $user->display_name,
        'approval_status' => 'pending_review',
    ]);

    $response = $this->actingAs($user)->put(route('contents.update', $content), [
        'category_id' => $content->category_id,
        'name' => 'Blocked edit',
        'description' => 'Blocked edit',
    ]);

    $response->assertForbidden();
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/ContentReviewAuthorizationTest.php --filter=user_cannot_edit_content_while_pending_review`

Expected: FAIL because existing controllers still allow edits too broadly.

- [ ] **Step 3: Write the minimal restriction implementation**

```php
public function content(ContentItem $content)
{
    $this->authorizeContentExport($content);
    // existing export logic...
}
```

```php
public function update(Request $request, ContentItem $content): RedirectResponse
{
    $this->authorizeContentEdit($content);
    // existing update logic...
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/ContentReviewAuthorizationTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ExportController.php app/Http/Controllers/ContentController.php app/Http/Controllers/SceneController.php tests/Feature/ContentReviewAuthorizationTest.php
git commit -m "feat: enforce phase 1 review restrictions"
```

## Self-Review

### Spec coverage

- Covered:
  - role `reviewer`
  - explicit email
  - content approval workflow fields
  - history table
  - backend permission changes
  - submit review / review transitions
  - admin-only export
- Deferred to later phases:
  - content list table redesign
  - content detail review UI
  - email notification
  - separate admin history menu

### Placeholder scan

- No `TODO` / `TBD`
- All tasks name exact files
- Each task includes test/run/implementation/verify/commit steps

### Type consistency

- Uses `approval_status` consistently
- Uses route names `contents.submit-review` and `contents.review`
- Uses helper names `authorizeContentView`, `authorizeContentEdit`, `authorizeContentReview`, `authorizeContentExport`, `authorizeSceneEdit`

