<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SceneCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_scene_from_mp4_and_store_converted_gif(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        config()->set('services.video_to_gif.url', 'http://convert.test/api/video-to-gif');
        config()->set('services.video_to_gif.key', 'secret-key');
        config()->set('services.video_to_gif.timeout', 30);

        Http::fake([
            'http://convert.test/api/video-to-gif' => Http::response([
                'status' => 'success',
                'gif_url' => 'http://convert.test/outputs/scene-1.gif',
            ], 200),
            'http://convert.test/outputs/scene-1.gif' => Http::response('GIF89a', 200, [
                'Content-Type' => 'image/gif',
            ]),
        ]);

        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Demo',
            'description' => 'Demo',
        ]);
        $content = ContentItem::create([
            'category_id' => $category->id,
            'name' => 'Story',
            'description' => 'Story',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('scenes.store', $content), [
                'name' => 'Scene 1',
                'scene_text' => 'Ngày xửa ngày xưa...',
                'video' => UploadedFile::fake()->create('scene.mp4', 128, 'video/mp4'),
                'image' => UploadedFile::fake()->image('scene-cover.png'),
                'duration_seconds' => 5,
            ]);

        $response->assertRedirect(route('contents.show', $content));

        $scene = Scene::query()->where('name', 'Scene 1')->first();

        $this->assertNotNull($scene);
        $this->assertSame('Ngày xửa ngày xưa...', $scene->scene_text);
        $this->assertNotNull($scene->gif_path);
        $this->assertSame('scene-1.gif', $scene->gif_original_name);
        $this->assertNotNull($scene->image_path);
        $this->assertSame('scene-cover.png', $scene->image_original_name);
        Storage::disk('public')->assertExists($scene->gif_path);
        Storage::disk('public')->assertExists($scene->image_path);
        $this->assertSame([], Storage::disk('local')->allFiles('tmp/videos'));
        Http::assertSent(fn ($request) => $request->url() === 'http://convert.test/api/video-to-gif'
            && $request->hasHeader('X-API-Key', 'secret-key'));
    }

    public function test_scene_is_not_created_when_convert_api_fails(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        config()->set('services.video_to_gif.url', 'http://convert.test/api/video-to-gif');
        config()->set('services.video_to_gif.key', 'secret-key');

        Http::fake([
            'http://convert.test/api/video-to-gif' => Http::response([
                'status' => 'error',
            ], 500),
        ]);

        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Demo',
            'description' => 'Demo',
        ]);
        $content = ContentItem::create([
            'category_id' => $category->id,
            'name' => 'Story',
            'description' => 'Story',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);

        $response = $this
            ->from(route('contents.show', $content))
            ->actingAs($user)
            ->post(route('scenes.store', $content), [
                'name' => 'Scene fail',
                'scene_text' => 'No gif',
                'video' => UploadedFile::fake()->create('scene.mp4', 128, 'video/mp4'),
            ]);

        $response->assertRedirect(route('contents.show', $content));
        $response->assertSessionHasErrors('video');
        $this->assertDatabaseMissing('scenes', [
            'name' => 'Scene fail',
        ]);
        $this->assertSame([], Storage::disk('local')->allFiles('tmp/videos'));
    }

    public function test_service_falls_back_to_default_api_path_when_only_host_is_configured(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        config()->set('services.video_to_gif.url', 'http://convert.test');
        config()->set('services.video_to_gif.key', 'secret-key');
        config()->set('services.video_to_gif.timeout', 30);

        Http::fake([
            'http://convert.test' => Http::response([
                'detail' => 'Method Not Allowed',
            ], 405),
            'http://convert.test/api/video-to-gif' => Http::response([
                'status' => 'success',
                'gif_url' => 'http://convert.test/outputs/fallback.gif',
            ], 200),
            'http://convert.test/outputs/fallback.gif' => Http::response('GIF89a', 200, [
                'Content-Type' => 'image/gif',
            ]),
        ]);

        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Demo',
            'description' => 'Demo',
        ]);
        $content = ContentItem::create([
            'category_id' => $category->id,
            'name' => 'Story',
            'description' => 'Story',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('scenes.store', $content), [
                'name' => 'Scene fallback',
                'video' => UploadedFile::fake()->create('scene.mp4', 128, 'video/mp4'),
            ]);

        $response->assertRedirect(route('contents.show', $content));
        $this->assertDatabaseHas('scenes', [
            'name' => 'Scene fallback',
        ]);
    }
}
