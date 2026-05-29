<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('description')->constrained('users')->nullOnDelete();
            $table->string('created_by_name')->nullable()->after('created_by');
        });

        Schema::table('scenes', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('next_transition_template_id')->constrained('users')->nullOnDelete();
            $table->string('created_by_name')->nullable()->after('created_by');
        });

        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('username');
            $table->string('export_type');
            $table->string('file_name');
            $table->text('data_scope')->nullable();
            $table->dateTime('exported_at');
            $table->index('exported_at');
        });

        $fallbackUser = DB::table('users')->orderByRaw("role = 'admin' desc")->orderBy('id')->first();

        if ($fallbackUser) {
            foreach (DB::table('content_items')->whereNull('created_by')->get() as $content) {
                DB::table('content_items')
                    ->where('id', $content->id)
                    ->update([
                        'created_by' => $fallbackUser->id,
                        'created_by_name' => $fallbackUser->full_name ?: $fallbackUser->name,
                        'updated_at' => now(),
                    ]);
            }

            foreach (DB::table('scenes')->whereNull('created_by')->get() as $scene) {
                $content = DB::table('content_items')->where('id', $scene->content_item_id)->first();

                DB::table('scenes')
                    ->where('id', $scene->id)
                    ->update([
                        'created_by' => $content->created_by ?? $fallbackUser->id,
                        'created_by_name' => $content->created_by_name ?? ($fallbackUser->full_name ?: $fallbackUser->name),
                        'updated_at' => now(),
                    ]);
            }
        }

        Schema::table('content_items', function (Blueprint $table) {
            $table->index(['created_by', 'created_at']);
        });

        Schema::table('scenes', function (Blueprint $table) {
            $table->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('created_by_name');
        });

        Schema::table('content_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('created_by_name');
        });

        Schema::dropIfExists('export_logs');
    }
};
