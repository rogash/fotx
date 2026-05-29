<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_photos', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
        });

        DB::table('event_photos')
            ->whereNull('public_id')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(200, function ($photos): void {
                foreach ($photos as $photo) {
                    DB::table('event_photos')
                        ->where('id', $photo->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });

        DB::table('orders')
            ->whereNull('public_id')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        Schema::table('event_photos', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
