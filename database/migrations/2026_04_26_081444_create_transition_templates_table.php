<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transition_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('gif_path')->nullable();
            $table->string('gif_original_name')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('audio_original_name')->nullable();
            $table->unsignedInteger('duration_seconds')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transition_templates');
    }
};
