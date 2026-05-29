<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_photos', function (Blueprint $table): void {
            $table->string('participant_code')->nullable()->after('filename');
            $table->text('search_keywords')->nullable()->after('participant_code');
            $table->index(['event_id', 'participant_code']);
        });
    }

    public function down(): void
    {
        Schema::table('event_photos', function (Blueprint $table): void {
            $table->dropIndex(['event_id', 'participant_code']);
            $table->dropColumn(['participant_code', 'search_keywords']);
        });
    }
};
