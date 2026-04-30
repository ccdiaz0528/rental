<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->date('fecha_vencimiento_soat')->nullable()->after('anio');
            $table->date('fecha_vencimiento_tecnomecanico')->nullable()->after('fecha_vencimiento_soat');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn(['fecha_vencimiento_soat', 'fecha_vencimiento_tecnomecanico']);
        });
    }
};
