<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->string('media_status')->default('completed')->after('audio_original_name');
            $table->text('media_error')->nullable()->after('media_status');
            $table->timestamp('media_started_at')->nullable()->after('media_error');
            $table->timestamp('media_completed_at')->nullable()->after('media_started_at');
            $table->unsignedTinyInteger('media_attempts')->default(0)->after('media_completed_at');
            $table->string('source_video_path')->nullable()->after('media_attempts');
            $table->string('source_video_original_name')->nullable()->after('source_video_path');
        });
    }

    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->dropColumn([
                'media_status',
                'media_error',
                'media_started_at',
                'media_completed_at',
                'media_attempts',
                'source_video_path',
                'source_video_original_name',
            ]);
        });
    }
};
