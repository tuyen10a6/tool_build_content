<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->string('approval_status')->default('draft')->after('description');
            $table->text('review_comment')->nullable()->after('approval_status');
            $table->foreignId('reviewed_by')->nullable()->after('review_comment')->constrained('users')->nullOnDelete();
            $table->string('reviewed_by_name')->nullable()->after('reviewed_by');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_name');
            $table->timestamp('submitted_at')->nullable()->after('reviewed_at');
            $table->unsignedInteger('revision_requested_count')->default(0)->after('submitted_at');
            $table->index(['approval_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['approval_status', 'created_at']);
            $table->dropColumn([
                'approval_status',
                'review_comment',
                'reviewed_by_name',
                'reviewed_at',
                'submitted_at',
                'revision_requested_count',
            ]);
        });
    }
};
