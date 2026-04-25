<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('gif_path')->nullable();
            $table->string('gif_original_name')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('audio_original_name')->nullable();
            $table->unsignedInteger('duration_seconds')->default(3);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenes');
    }
};
