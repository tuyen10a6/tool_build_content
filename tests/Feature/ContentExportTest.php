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

class ContentExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_export_uses_story_markdown_and_gif_files_in_root_folder(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'History',
            'description' => 'History',
        ]);

        $content = ContentItem::create([
            'category_id' => $category->id,
            'name' => 'Tran Quoc Toan',
            'description' => 'Story about Tran Quoc Toan',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);

        $sceneOne = Scene::create([
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

        $sceneTwo = Scene::create([
            'content_item_id' => $content->id,
            'scene_type' => 'main',
            'name' => 'Scene 2',
            'scene_text' => 'Doan van thu hai',
            'gif_path' => 'scenes/gifs/scene-2.gif',
            'gif_original_name' => 'scene-2.gif',
            'duration_seconds' => 4,
            'position' => 2,
            'sort_order' => 2,
            'position_label' => '2',
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);

        Scene::create([
            'content_item_id' => $content->id,
            'scene_type' => 'transition',
            'name' => 'Transition 1-2',
            'scene_text' => null,
            'gif_path' => 'scenes/gifs/transition.gif',
            'gif_original_name' => 'transition.gif',
            'duration_seconds' => 1,
            'position' => 1,
            'sort_order' => 3,
            'position_label' => '1-2',
            'from_scene_id' => $sceneOne->id,
            'to_scene_id' => $sceneTwo->id,
            'created_by' => $user->id,
            'created_by_name' => $user->display_name,
        ]);

        Storage::disk('public')->put($sceneOne->gif_path, 'gif-one');
        Storage::disk('public')->put($sceneTwo->gif_path, 'gif-two');
        Storage::disk('public')->put('scenes/gifs/transition.gif', 'gif-transition');

        $response = $this
            ->actingAs($admin)
            ->get(route('exports.contents', $content));

        $response->assertOk();
        $response->assertDownload('tranquoctoan.zip');

        $zipFile = $response->baseResponse->getFile();
        $this->assertNotNull($zipFile);

        $zip = new ZipArchive();
        $opened = $zip->open($zipFile->getPathname());
        $this->assertTrue($opened === true);

        $entries = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entries[] = $zip->getNameIndex($index);
        }

        $this->assertSame([
            'tranquoctoan/story.md',
            'tranquoctoan/TQT1.GIF',
            'tranquoctoan/TQT2.GIF',
        ], $entries);

        $story = $zip->getFromName('tranquoctoan/story.md');
        $this->assertNotFalse($story);
        $this->assertStringContainsString('Doan van thu nhat /tranquoctoan/TQT1.GIF', $story);
        $this->assertStringContainsString('Doan van thu hai /tranquoctoan/TQT2.GIF', $story);
        $this->assertStringNotContainsString('/tranquoctoan/TQT1.GIF.', $story);
        $this->assertStringNotContainsString('/tranquoctoan/TQT2.GIF.', $story);
        $this->assertStringNotContainsString('[tranquoctoan/TQT1.GIF](./TQT1.GIF)', $story);
        $this->assertStringNotContainsString('[tranquoctoan/TQT2.GIF](./TQT2.GIF)', $story);
        $this->assertStringNotContainsString('content.md', $story);
        $this->assertStringNotContainsString('transition', strtolower($story));

        $this->assertSame('gif-one', $zip->getFromName('tranquoctoan/TQT1.GIF'));
        $this->assertSame('gif-two', $zip->getFromName('tranquoctoan/TQT2.GIF'));

        $zip->close();
    }
}
