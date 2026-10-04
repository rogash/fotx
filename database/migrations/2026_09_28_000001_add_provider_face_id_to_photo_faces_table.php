<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photo_faces', function (Blueprint $table) {
            $table->string('provider_face_id')->nullable()->after('event_photo_id');
            $table->index(['event_id', 'provider_face_id']);
        });
    }

    public function down(): void
    {
        Schema::table('photo_faces', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'provider_face_id']);
            $table->dropColumn('provider_face_id');
        });
    }
};
