<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->index('fecha_vencimiento_soat');
            $table->index('fecha_vencimiento_tecnomecanico');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropIndex(['fecha_vencimiento_soat']);
            $table->dropIndex(['fecha_vencimiento_tecnomecanico']);
        });
    }
};
