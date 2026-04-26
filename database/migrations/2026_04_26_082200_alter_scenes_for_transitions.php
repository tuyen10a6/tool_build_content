<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->string('scene_type')->default('main')->after('content_item_id');
            $table->unsignedInteger('sort_order')->default(1)->after('position');
            $table->string('position_label')->nullable()->after('sort_order');
            $table->foreignId('from_scene_id')->nullable()->after('position_label')->constrained('scenes')->nullOnDelete();
            $table->foreignId('to_scene_id')->nullable()->after('from_scene_id')->constrained('scenes')->nullOnDelete();
            $table->foreignId('transition_template_id')->nullable()->after('to_scene_id')->constrained('transition_templates')->nullOnDelete();
            $table->foreignId('next_transition_template_id')->nullable()->after('transition_template_id')->constrained('transition_templates')->nullOnDelete();
        });

        DB::table('scenes')->where('scene_type', 'main')->update([
            'sort_order' => DB::raw('position'),
            'position_label' => DB::raw('CAST(position AS CHAR)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_transition_template_id');
            $table->dropConstrainedForeignId('transition_template_id');
            $table->dropConstrainedForeignId('to_scene_id');
            $table->dropConstrainedForeignId('from_scene_id');
            $table->dropColumn(['scene_type', 'sort_order', 'position_label']);
        });
    }
};
