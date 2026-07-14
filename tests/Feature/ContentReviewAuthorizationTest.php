<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentReviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_view_but_cannot_edit_or_export_content(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $owner = User::factory()->create(['role' => 'user']);
        $content = $this->createContentForUser($owner, [
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

    public function test_user_cannot_edit_content_while_pending_review(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $content = $this->createContentForUser($user, [
            'approval_status' => 'pending_review',
        ]);

        $response = $this->actingAs($user)->put(route('contents.update', $content), [
            'category_id' => $content->category_id,
            'name' => 'Blocked edit',
            'description' => 'Blocked edit',
        ]);

        $response->assertForbidden();
    }

    public function test_reviewer_sees_all_contents_and_creator_filter_options(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $ownerA = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Nguyen Van A',
            'name' => 'Nguyen Van A',
        ]);
        $ownerB = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Tran Thi B',
            'name' => 'Tran Thi B',
        ]);

        $contentA = $this->createContentForUser($ownerA, ['name' => 'Story A']);
        $contentB = $this->createContentForUser($ownerB, ['name' => 'Story B']);

        $response = $this->actingAs($reviewer)->get(route('contents.index'));

        $response->assertOk();
        $response->assertSee('Story A');
        $response->assertSee('Story B');
        $response->assertSee($ownerA->display_name);
        $response->assertSee($ownerB->display_name);
        $response->assertSee(route('contents.show', $contentA));
        $response->assertSee(route('contents.show', $contentB));
    }

    public function test_reviewer_can_edit_own_content_while_draft(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $content = $this->createContentForUser($reviewer, [
            'approval_status' => 'draft',
        ]);

        $response = $this->actingAs($reviewer)->put(route('contents.update', $content), [
            'category_id' => $content->category_id,
            'name' => 'Reviewer updated content',
            'description' => 'Updated by reviewer',
        ]);

        $response->assertRedirect(route('contents.show', $content));
        $this->assertDatabaseHas('content_items', [
            'id' => $content->id,
            'name' => 'Reviewer updated content',
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
