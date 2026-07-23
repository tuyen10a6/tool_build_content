<?php

namespace Tests\Feature;

use App\Jobs\ProcessSceneMediaJob;
use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Scene;
use App\Models\User;
use App\Services\TextToAudioService;
use App\Services\VideoToGifService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SceneCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_scene_and_dispatch_media_job(): void
    {
        Queue::fake();
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create();
        $content = $this->createContentForUser($user);

        $response = $this
            ->actingAs($user)
            ->post(route('scenes.store', $content), [
                'name' => 'Scene 1',
                'scene_text' => 'Ngày xửa ngày xưa...',
                'video' => UploadedFile::fake()->create('scene.mp4', 128, 'video/mp4'),
                'image' => UploadedFile::fake()->image('scene-cover.png'),
            ]);

        $response->assertRedirect(route('contents.show', $content));

        $scene = Scene::query()->where('name', 'Scene 1')->first();

        $this->assertNotNull($scene);
        $this->assertSame('Ngày xửa ngày xưa...', $scene->scene_text);
        $this->assertSame('pending', $scene->media_status);
        $this->assertNotNull($scene->source_video_path);
        $this->assertSame('scene.mp4', $scene->source_video_original_name);
        $this->assertNull($scene->gif_path);
        $this->assertNull($scene->audio_path);
        $this->assertNotNull($scene->image_path);
        $this->assertSame('scene-cover.png', $scene->image_original_name);
        Storage::disk('public')->assertExists($scene->image_path);
        Storage::disk('local')->assertExists($scene->source_video_path);
        Queue::assertPushed(ProcessSceneMediaJob::class, function (ProcessSceneMediaJob $job) use ($scene) {
            return $job->sceneId === $scene->id;
        });
    }

    public function test_user_can_create_scene_with_png_source_media_and_dispatch_media_job(): void
    {
        Queue::fake();
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create();
        $content = $this->createContentForUser($user);

        $response = $this
            ->actingAs($user)
            ->post(route('scenes.store', $content), [
                'name' => 'Scene image source',
                'scene_text' => 'Ảnh nguồn để convert',
                'video' => UploadedFile::fake()->image('scene-source.png'),
            ]);

        $response->assertRedirect(route('contents.show', $content));

        $scene = Scene::query()->where('name', 'Scene image source')->first();

        $this->assertNotNull($scene);
        $this->assertSame('pending', $scene->media_status);
        $this->assertNotNull($scene->source_video_path);
        $this->assertSame('scene-source.png', $scene->source_video_original_name);
        $this->assertSame(3, $scene->duration_seconds);
        Storage::disk('local')->assertExists($scene->source_video_path);
        Queue::assertPushed(ProcessSceneMediaJob::class, function (ProcessSceneMediaJob $job) use ($scene) {
            return $job->sceneId === $scene->id;
        });
    }

    public function test_scene_media_job_completes_and_cleans_up_temp_video(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        config()->set('services.video_to_gif.url', 'http://convert.test/api/video-to-gif');
        config()->set('services.video_to_gif.key', 'secret-key');
        config()->set('services.video_to_gif.timeout', 30);
        config()->set('services.text_to_audio.url', 'http://convert.test/api/text-to-audio');
        config()->set('services.text_to_audio.key', 'secret-key');
        config()->set('services.text_to_audio.timeout', 30);

        Http::fake([
            'http://convert.test/api/video-to-gif' => Http::response([
                'status' => 'success',
                'gif_url' => 'http://convert.test/outputs/scene-1.gif',
            ], 200),
            'http://convert.test/api/text-to-audio' => Http::response([
                'status' => 'success',
                'audio_url' => 'http://convert.test/outputs/scene-1.mp3',
                'duration_seconds' => 9,
            ], 200),
            'http://convert.test/outputs/scene-1.gif' => Http::response('GIF89a', 200, [
                'Content-Type' => 'image/gif',
            ]),
            'http://convert.test/outputs/scene-1.mp3' => Http::response('fake-audio', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        $user = User::factory()->create();
        $content = $this->createContentForUser($user);

        Storage::disk('local')->put('tmp/scene-videos/source-scene.mp4', 'fake-mp4');

        $scene = Scene::create([
            'content_item_id' => $content->id,
            'scene_type' => 'main',
            'name' => 'Scene 1',
            'scene_text' => 'Ngày xửa ngày xưa...',
            'duration_seconds' => 3,
            'position' => 1,
            'sort_order' => 1,
            'position_label' => '1',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
            'media_status' => 'pending',
            'source_video_path' => 'tmp/scene-videos/source-scene.mp4',
            'source_video_original_name' => 'scene.mp4',
        ]);

        (new ProcessSceneMediaJob($scene->id))->handle(
            app(TextToAudioService::class),
            app(VideoToGifService::class),
        );

        $scene->refresh();

        $this->assertSame('completed', $scene->media_status);
        $this->assertNull($scene->media_error);
        $this->assertNotNull($scene->media_started_at);
        $this->assertNotNull($scene->media_completed_at);
        $this->assertSame(1, $scene->media_attempts);
        $this->assertSame('scene-1.gif', $scene->gif_original_name);
        $this->assertSame('scene-1.mp3', $scene->audio_original_name);
        $this->assertSame(9, $scene->duration_seconds);
        Storage::disk('public')->assertExists($scene->gif_path);
        Storage::disk('public')->assertExists($scene->audio_path);
        Storage::disk('local')->assertMissing('tmp/scene-videos/source-scene.mp4');
    }

    public function test_service_falls_back_to_default_api_path_when_only_host_is_configured(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        config()->set('services.video_to_gif.url', 'http://convert.test');
        config()->set('services.video_to_gif.key', 'secret-key');
        config()->set('services.video_to_gif.timeout', 30);
        config()->set('services.text_to_audio.url', '');
        config()->set('services.text_to_audio.key', 'secret-key');
        config()->set('services.text_to_audio.timeout', 30);

        Http::fake([
            'http://convert.test' => Http::response([
                'detail' => 'Method Not Allowed',
            ], 405),
            'http://convert.test/api/video-to-gif' => Http::response([
                'status' => 'success',
                'gif_url' => 'http://convert.test/outputs/fallback.gif',
            ], 200),
            'http://convert.test/api/text-to-audio' => Http::response([
                'status' => 'success',
                'audio_url' => 'http://convert.test/outputs/fallback.mp3',
                'duration_seconds' => 6,
            ], 200),
            'http://convert.test/outputs/fallback.gif' => Http::response('GIF89a', 200, [
                'Content-Type' => 'image/gif',
            ]),
            'http://convert.test/outputs/fallback.mp3' => Http::response('fake-audio', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        $user = User::factory()->create();
        $content = $this->createContentForUser($user);

        Storage::disk('local')->put('tmp/scene-videos/source-fallback.mp4', 'fake-mp4');

        $scene = Scene::create([
            'content_item_id' => $content->id,
            'scene_type' => 'main',
            'name' => 'Scene fallback',
            'scene_text' => 'fallback text',
            'duration_seconds' => 3,
            'position' => 1,
            'sort_order' => 1,
            'position_label' => '1',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
            'media_status' => 'pending',
            'source_video_path' => 'tmp/scene-videos/source-fallback.mp4',
            'source_video_original_name' => 'scene.mp4',
        ]);

        (new ProcessSceneMediaJob($scene->id))->handle(
            app(TextToAudioService::class),
            app(VideoToGifService::class),
        );

        $this->assertDatabaseHas('scenes', [
            'id' => $scene->id,
            'duration_seconds' => 6,
            'media_status' => 'completed',
        ]);
    }

    public function test_scene_media_job_marks_scene_as_failed_when_audio_generation_fails(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        config()->set('services.video_to_gif.url', 'http://convert.test/api/video-to-gif');
        config()->set('services.video_to_gif.key', 'secret-key');
        config()->set('services.text_to_audio.url', 'http://convert.test/api/text-to-audio');
        config()->set('services.text_to_audio.key', 'secret-key');

        Http::fake([
            'http://convert.test/api/text-to-audio' => Http::response([
                'status' => 'error',
            ], 500),
        ]);

        $user = User::factory()->create();
        $content = $this->createContentForUser($user);

        Storage::disk('local')->put('tmp/scene-videos/source-failed.mp4', 'fake-mp4');

        $scene = Scene::create([
            'content_item_id' => $content->id,
            'scene_type' => 'main',
            'name' => 'Scene fail',
            'scene_text' => 'audio fail',
            'duration_seconds' => 3,
            'position' => 1,
            'sort_order' => 1,
            'position_label' => '1',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
            'media_status' => 'pending',
            'source_video_path' => 'tmp/scene-videos/source-failed.mp4',
            'source_video_original_name' => 'scene.mp4',
        ]);

        try {
            (new ProcessSceneMediaJob($scene->id))->handle(
                app(TextToAudioService::class),
                app(VideoToGifService::class),
            );
            $this->fail('Expected queued media job to throw when audio generation fails.');
        } catch (\Throwable) {
        }

        $scene->refresh();

        $this->assertSame('failed', $scene->media_status);
        $this->assertNotNull($scene->media_error);
        $this->assertSame(1, $scene->media_attempts);
        Storage::disk('local')->assertMissing('tmp/scene-videos/source-failed.mp4');
        $this->assertSame([], Storage::disk('public')->allFiles('scenes/gifs'));
    }

    public function test_updating_scene_dispatches_media_job_and_resets_media_status(): void
    {
        Queue::fake();
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create();
        $content = $this->createContentForUser($user);

        $scene = Scene::create([
            'content_item_id' => $content->id,
            'scene_type' => 'main',
            'name' => 'Scene 1',
            'scene_text' => 'old text',
            'gif_path' => 'scenes/gifs/old.gif',
            'gif_original_name' => 'old.gif',
            'audio_path' => 'scenes/audios/old.mp3',
            'audio_original_name' => 'old.mp3',
            'duration_seconds' => 3,
            'position' => 1,
            'sort_order' => 1,
            'position_label' => '1',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
            'media_status' => 'completed',
            'media_error' => 'old error',
        ]);

        Storage::disk('public')->put('scenes/audios/old.mp3', 'old-audio');
        Storage::disk('public')->put('scenes/gifs/old.gif', 'old-gif');

        $response = $this
            ->actingAs($user)
            ->put(route('scenes.update', $scene), [
                'name' => 'Scene 1 updated',
                'scene_text' => 'new generated text',
                'position' => 1,
            ]);

        $response->assertRedirect(route('scenes.show', $scene));

        $scene->refresh();

        $this->assertSame('Scene 1 updated', $scene->name);
        $this->assertSame('new generated text', $scene->scene_text);
        $this->assertSame('pending', $scene->media_status);
        $this->assertNull($scene->media_error);
        $this->assertSame('old.gif', $scene->gif_original_name);
        $this->assertSame('old.mp3', $scene->audio_original_name);
        Queue::assertPushed(ProcessSceneMediaJob::class, function (ProcessSceneMediaJob $job) use ($scene) {
            return $job->sceneId === $scene->id;
        });
    }

    public function test_updating_scene_accepts_png_source_media_and_keeps_existing_duration_when_image_has_no_duration(): void
    {
        Queue::fake();
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create();
        $content = $this->createContentForUser($user);

        $scene = Scene::create([
            'content_item_id' => $content->id,
            'scene_type' => 'main',
            'name' => 'Scene image update',
            'scene_text' => 'old text',
            'duration_seconds' => 8,
            'position' => 1,
            'sort_order' => 1,
            'position_label' => '1',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
            'media_status' => 'completed',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('scenes.update', $scene), [
                'name' => 'Scene image update',
                'scene_text' => 'updated text',
                'position' => 1,
                'video' => UploadedFile::fake()->image('scene-update.png'),
            ]);

        $response->assertRedirect(route('scenes.show', $scene));

        $scene->refresh();

        $this->assertSame('pending', $scene->media_status);
        $this->assertSame('scene-update.png', $scene->source_video_original_name);
        $this->assertSame(8, $scene->duration_seconds);
        Storage::disk('local')->assertExists($scene->source_video_path);
        Queue::assertPushed(ProcessSceneMediaJob::class, function (ProcessSceneMediaJob $job) use ($scene) {
            return $job->sceneId === $scene->id;
        });
    }

    private function createContentForUser(User $user): ContentItem
    {
        $category = Category::create([
            'name' => 'Demo',
            'description' => 'Demo',
        ]);

        return ContentItem::create([
            'category_id' => $category->id,
            'name' => 'Story',
            'description' => 'Story',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);
    }
}
