<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_review_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_item_id')->constrained('content_items')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('comment')->nullable();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acted_by_name')->nullable();
            $table->string('acted_role')->nullable();
            $table->timestamps();
            $table->index(['content_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_review_histories');
    }
};
