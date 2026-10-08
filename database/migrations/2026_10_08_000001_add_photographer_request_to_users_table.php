<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('photographer_requested_at')->nullable()->after('role')->index();
            $table->string('photographer_portfolio')->nullable()->after('photographer_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['photographer_requested_at']);
            $table->dropColumn(['photographer_requested_at', 'photographer_portfolio']);
        });
    }
};
