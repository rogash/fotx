<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->date('event_date')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price_per_photo', 10, 2);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignId('cover_photo_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
