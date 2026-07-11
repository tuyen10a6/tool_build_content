<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReviewerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_reviewer_with_email(): void
    {
        $admin = User::factory()->admin()->create();

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
}
