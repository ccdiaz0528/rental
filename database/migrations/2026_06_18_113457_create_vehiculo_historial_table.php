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
        Schema::create('vehiculo_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('persona_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cuota_diaria', 12, 2)->default(0);
            $table->decimal('administracion', 12, 2)->default(0);
            $table->timestamp('fecha_inicio')->useCurrent();
            $table->timestamp('fecha_fin')->nullable();
            $table->timestamps();

            $table->index('fecha_inicio');
            $table->index('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_historial');
    }
};
