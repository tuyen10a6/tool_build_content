<?php

namespace Tests\Feature;

use App\Mail\ContentReviewOutcomeMail;
use App\Mail\ContentSubmittedForReviewMail;
use App\Models\Category;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContentReviewNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_review_queues_mail_to_active_admins_and_reviewers_only(): void
    {
        Mail::fake();

        $owner = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'email' => 'owner@example.com',
        ]);
        $activeAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email' => 'admin.active@example.com',
        ]);
        $lockedAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'locked',
            'email' => 'admin.locked@example.com',
        ]);
        $activeReviewer = User::factory()->create([
            'role' => 'reviewer',
            'status' => 'active',
            'email' => 'reviewer.active@example.com',
        ]);
        $lockedReviewer = User::factory()->create([
            'role' => 'reviewer',
            'status' => 'locked',
            'email' => 'reviewer.locked@example.com',
        ]);
        $content = $this->createContentForUser($owner, [
            'approval_status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->post(route('contents.submit-review', $content))
            ->assertRedirect(route('contents.show', $content));

        Mail::assertQueued(ContentSubmittedForReviewMail::class, fn (ContentSubmittedForReviewMail $mail): bool => $mail->hasTo($activeAdmin->email)
            && str_contains($mail->envelope()->subject, $owner->full_name));
        Mail::assertQueued(ContentSubmittedForReviewMail::class, fn (ContentSubmittedForReviewMail $mail): bool => $mail->hasTo($activeReviewer->email)
            && str_contains($mail->envelope()->subject, $owner->full_name));
        Mail::assertNotQueued(ContentSubmittedForReviewMail::class, fn (ContentSubmittedForReviewMail $mail): bool => $mail->hasTo($lockedAdmin->email));
        Mail::assertNotQueued(ContentSubmittedForReviewMail::class, fn (ContentSubmittedForReviewMail $mail): bool => $mail->hasTo($lockedReviewer->email));
    }

    public function test_review_result_queues_mail_to_active_content_owner_only(): void
    {
        Mail::fake();

        $owner = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'email' => 'owner@example.com',
        ]);
        $reviewer = User::factory()->create([
            'role' => 'reviewer',
            'status' => 'active',
            'email' => 'reviewer@example.com',
        ]);

        $content = $this->createContentForUser($owner, [
            'approval_status' => 'pending_review',
        ]);

        $this->actingAs($reviewer)
            ->post(route('contents.review', $content), [
                'approval_status' => 'needs_revision',
                'review_comment' => 'Bổ sung phân cảnh kết thúc',
            ])
            ->assertRedirect(route('contents.show', $content));

        Mail::assertQueued(ContentReviewOutcomeMail::class, function (ContentReviewOutcomeMail $mail) use ($owner): bool {
            return $mail->hasTo($owner->email)
                && str_contains($mail->envelope()->subject, $owner->full_name);
        });
    }

    public function test_admin_review_result_queues_mail_to_active_content_owner_only(): void
    {
        Mail::fake();

        $owner = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'email' => 'owner@example.com',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email' => 'admin@example.com',
        ]);

        $content = $this->createContentForUser($owner, [
            'approval_status' => 'pending_review',
        ]);

        $this->actingAs($admin)
            ->post(route('contents.review', $content), [
                'approval_status' => 'approved',
                'review_comment' => 'Đã duyệt nội dung',
            ])
            ->assertRedirect(route('contents.show', $content));

        Mail::assertQueued(ContentReviewOutcomeMail::class, function (ContentReviewOutcomeMail $mail) use ($owner): bool {
            return $mail->hasTo($owner->email)
                && str_contains($mail->envelope()->subject, $owner->full_name);
        });
    }

    private function createContentForUser(User $user, array $overrides = []): ContentItem
    {
        $category = Category::create([
            'name' => 'Demo '.uniqid(),
            'description' => 'Demo',
        ]);

        return ContentItem::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Story',
            'description' => 'Story',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ], $overrides));
    }
}
