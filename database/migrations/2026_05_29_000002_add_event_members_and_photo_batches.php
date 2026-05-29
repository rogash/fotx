<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'photographer', 'assistant', 'viewer'])->default('photographer');
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        Schema::create('photo_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'uploading', 'processing', 'done', 'failed'])->default('pending');
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('processed_files')->default(0);
            $table->unsignedInteger('failed_files')->default(0);
            $table->unsignedBigInteger('original_total_bytes')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['uploaded_by', 'created_at']);
        });

        Schema::table('event_photos', function (Blueprint $table): void {
            $table->foreignId('photo_batch_id')->nullable()->after('event_id')->constrained('photo_batches')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->after('photo_batch_id')->constrained('users')->nullOnDelete();
            $table->foreignId('photographer_id')->nullable()->after('uploaded_by')->constrained('users')->nullOnDelete();
            $table->string('file_hash', 64)->nullable()->after('filename');
            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'photographer_id']);
            $table->index(['event_id', 'file_hash']);
        });

        DB::table('events')
            ->orderBy('id')
            ->select(['id', 'user_id', 'created_at', 'updated_at'])
            ->chunkById(200, function ($events): void {
                foreach ($events as $event) {
                    DB::table('event_members')->updateOrInsert(
                        ['event_id' => $event->id, 'user_id' => $event->user_id],
                        [
                            'role' => 'owner',
                            'permissions' => null,
                            'created_at' => $event->created_at,
                            'updated_at' => $event->updated_at,
                        ],
                    );

                    DB::table('event_photos')
                        ->where('event_id', $event->id)
                        ->whereNull('uploaded_by')
                        ->update([
                            'uploaded_by' => $event->user_id,
                            'photographer_id' => $event->user_id,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('event_photos', function (Blueprint $table): void {
            $table->dropIndex(['event_id', 'file_hash']);
            $table->dropIndex(['event_id', 'photographer_id']);
            $table->dropIndex(['event_id', 'status']);
            $table->dropConstrainedForeignId('photo_batch_id');
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropConstrainedForeignId('photographer_id');
            $table->dropColumn('file_hash');
        });

        Schema::dropIfExists('photo_batches');
        Schema::dropIfExists('event_members');
    }
};
