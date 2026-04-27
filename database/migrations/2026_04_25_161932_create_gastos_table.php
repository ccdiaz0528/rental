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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('restrict');
            $table->foreignId('persona_id')->nullable()->constrained('personas')->onDelete('set null');
            $table->date('fecha');
            $table->enum('categoria', [
                'mantenimiento',
                'aceite',
                'lavado',
                'tanqueada',
                'llantas',
                'frenos',
                'electrico',
                'multa',
                'fotomulta',
                'prestamo',
                'otro'
            ]);
            $table->decimal('valor', 10, 2);
            $table->text('detalle')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
