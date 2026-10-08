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
        Schema::table('control_diarios', function (Blueprint $table) {
            $table->index(['vehiculo_id', 'fecha'], 'cd_vehiculo_fecha_index');
        });

        Schema::table('vehiculo_historial', function (Blueprint $table) {
            $table->index(['vehiculo_id', 'fecha_inicio', 'fecha_fin'], 'vh_vehiculo_fechas_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('control_diarios', function (Blueprint $table) {
            $table->dropIndex('cd_vehiculo_fecha_index');
        });

        Schema::table('vehiculo_historial', function (Blueprint $table) {
            $table->dropIndex('vh_vehiculo_fechas_index');
        });
    }
};
