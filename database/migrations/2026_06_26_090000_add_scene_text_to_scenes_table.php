<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->text('scene_text')->nullable()->after('name');
            $table->string('image_path')->nullable()->after('gif_original_name');
            $table->string('image_original_name')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->dropColumn([
                'scene_text',
                'image_path',
                'image_original_name',
            ]);
        });
    }
};
