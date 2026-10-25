<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Buscas na vitrine comparam a selfie com vários eventos, então não têm um
 * evento único: o event_id fica nulo e os resultados guardam o evento de cada foto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_searches', function (Blueprint $table) {
            $table->string('scope', 20)->default('event')->after('id')->index();
            $table->foreignId('event_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('face_searches')->whereNull('event_id')->delete();

        Schema::table('face_searches', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable(false)->change();
            $table->dropColumn('scope');
        });
    }
};
