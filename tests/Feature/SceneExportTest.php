<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class SceneExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeScene(): Scene
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'History',
            'description' => 'History',
        ]);

        $content = ContentItem::create([
            'category_id' => $category->id,
            'name' => 'Tran Quoc Toan',
            'description' => 'Story',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);

        $scene = Scene::create([
            'content_item_id' => $content->id,
            'scene_type' => 'main',
            'name' => 'Scene 1',
            'scene_text' => 'Doan van thu nhat',
            'gif_path' => 'scenes/gifs/scene-1.gif',
            'gif_original_name' => 'scene-1.gif',
            'duration_seconds' => 3,
            'position' => 1,
            'sort_order' => 1,
            'position_label' => '1',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);

        Storage::disk('public')->put(
            $scene->gif_path,
            file_get_contents(__DIR__.'/../Fixtures/sample-480x320.gif'),
        );

        return $scene;
    }

    public function test_admin_can_export_scene_zip_with_markdown_and_resized_gif(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $scene = $this->makeScene();

        $response = $this->actingAs($admin)->get(route('exports.scenes', $scene));

        $response->assertOk();
        $response->assertDownload('scene-1.zip');

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()) === true);

        $entries = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entries[] = $zip->getNameIndex($index);
        }

        $this->assertSame(['scene-1/scene.md', 'scene-1/scene-1.gif'], $entries);

        $markdown = $zip->getFromName('scene-1/scene.md');
        $this->assertStringContainsString('# Scene 1', $markdown);
        $this->assertStringContainsString('- Content: Tran Quoc Toan', $markdown);
        $this->assertStringContainsString('- Nội dung: Doan van thu nhat', $markdown);

        $size = getimagesizefromstring($zip->getFromName('scene-1/scene-1.gif'));
        $this->assertNotFalse($size);
        $this->assertSame([320, 240], [$size[0], $size[1]]);

        $zip->close();
    }

    public function test_non_admin_cannot_export_scene(): void
    {
        Storage::fake('public');

        $scene = $this->makeScene();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('exports.scenes', $scene))
            ->assertForbidden();
    }

    public function test_scene_export_fails_instead_of_sending_unresized_gif_when_ffmpeg_is_missing(): void
    {
        Storage::fake('public');
        config(['services.ffmpeg.binary' => '/nonexistent/ffmpeg-binary']);

        $admin = User::factory()->admin()->create();
        $scene = $this->makeScene();

        $response = $this->actingAs($admin)->get(route('exports.scenes', $scene));

        $response->assertStatus(500);
        $this->assertFalse($response->headers->has('Content-Disposition'));
    }
}
