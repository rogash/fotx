<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('original_path');
            $table->string('watermarked_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->enum('status', ['uploaded', 'processing', 'ready', 'failed'])->default('uploaded');
            $table->timestamps();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreign('cover_photo_id')->references('id')->on('event_photos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['cover_photo_id']);
        });

        Schema::dropIfExists('event_photos');
    }
};
