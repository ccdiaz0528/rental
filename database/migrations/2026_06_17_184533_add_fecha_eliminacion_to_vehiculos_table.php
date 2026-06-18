<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->timestamp('fecha_eliminacion')->nullable()->after('restored_at');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn('fecha_eliminacion');
        });
    }
};
