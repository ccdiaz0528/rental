<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_diarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->date('fecha');
            $table->boolean('trabajo')->default(true);
            $table->decimal('valor_generado', 10, 2)->default(0);
            $table->decimal('gasto', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['vehiculo_id', 'fecha']);
        });

        if (Schema::hasTable('pagos_diarios')) {
            $pagos = DB::table('pagos_diarios')->get();

            foreach ($pagos as $pago) {
                DB::table('control_diarios')->updateOrInsert(
                    [
                        'vehiculo_id' => $pago->vehiculo_id,
                        'fecha' => $pago->fecha,
                    ],
                    [
                        'trabajo' => (float) $pago->valor > 0,
                        'valor_generado' => $pago->valor,
                        'gasto' => 0,
                        'observaciones' => $pago->observaciones,
                        'created_at' => $pago->created_at,
                        'updated_at' => $pago->updated_at,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('control_diarios');
    }
};
