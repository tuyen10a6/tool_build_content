<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_user_cannot_log_in(): void
    {
        $user = User::factory()->locked()->create([
            'username' => 'locked-user',
        ]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_regular_user_only_sees_owned_content(): void
    {
        $user = User::factory()->create([
            'full_name' => 'User A',
            'name' => 'User A',
        ]);
        $otherUser = User::factory()->create([
            'full_name' => 'User B',
            'name' => 'User B',
        ]);
        $category = Category::create([
            'name' => 'Demo',
            'description' => 'Demo',
        ]);

        $ownedContent = ContentItem::create([
            'category_id' => $category->id,
            'name' => 'Owned content',
            'description' => null,
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);

        $otherContent = ContentItem::create([
            'category_id' => $category->id,
            'name' => 'Other content',
            'description' => null,
            'created_by' => $otherUser->id,
            'created_by_name' => $otherUser->display_name,
        ]);

        $response = $this->actingAs($user)->get('/contents');

        $response->assertOk();
        $response->assertSee('Owned content');
        $response->assertDontSee('Other content');

        $this->actingAs($user)->get('/contents/'.$ownedContent->fresh()->id)->assertOk();
        $this->actingAs($user)->get('/contents/'.$otherContent->id)->assertForbidden();
    }

    public function test_regular_user_cannot_access_admin_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports');

        $response->assertForbidden();
    }
}
