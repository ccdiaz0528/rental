<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->index('persona_id');
            $table->index('estado');
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->index('vehiculo_id');
            $table->index('persona_id');
            $table->index(['estado', 'tipo']);
        });

        Schema::table('control_diarios', function (Blueprint $table) {
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropIndex(['persona_id']);
            $table->dropIndex(['estado']);
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->dropIndex(['vehiculo_id']);
            $table->dropIndex(['persona_id']);
            $table->dropIndex(['estado', 'tipo']);
        });

        Schema::table('control_diarios', function (Blueprint $table) {
            $table->dropIndex(['fecha']);
        });
    }
};
