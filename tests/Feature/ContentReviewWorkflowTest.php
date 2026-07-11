<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_review_workflow_columns_and_history_model_are_available(): void
    {
        $user = User::factory()->create();
        $content = $this->createContentForUser($user);

        $content->update([
            'approval_status' => 'draft',
            'review_comment' => 'Need revision',
            'reviewed_by' => $user->id,
            'reviewed_by_name' => $user->display_name,
            'revision_requested_count' => 1,
        ]);

        $history = $content->reviewHistories()->create([
            'from_status' => 'pending_review',
            'to_status' => 'needs_revision',
            'comment' => 'Need revision',
            'acted_by' => $user->id,
            'acted_by_name' => $user->display_name,
            'acted_role' => 'reviewer',
        ]);

        $this->assertSame('draft', $content->fresh()->approval_status);
        $this->assertSame('needs_revision', $history->to_status);
    }

    public function test_user_can_submit_owned_content_for_review(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $content = $this->createContentForUser($user, [
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

    public function test_reviewer_can_mark_pending_content_as_needs_revision(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $owner = User::factory()->create(['role' => 'user']);
        $content = $this->createContentForUser($owner, [
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
