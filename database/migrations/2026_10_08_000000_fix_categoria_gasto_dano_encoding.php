<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Solo repara bases donde el enum quedó con 'daño' doble codificado ('daÃ±o').
        $columna = DB::selectOne("SHOW COLUMNS FROM control_diarios LIKE 'categoria_gasto'");

        if (! $columna || ! str_contains($columna->Type, 'daÃ±o')) {
            return;
        }

        DB::statement('ALTER TABLE control_diarios MODIFY categoria_gasto VARCHAR(20) NULL');
        DB::table('control_diarios')
            ->where('categoria_gasto', 'daÃ±o')
            ->update(['categoria_gasto' => 'daño']);
        DB::statement("ALTER TABLE control_diarios MODIFY categoria_gasto ENUM('daño','mantenimiento','multa','otro') NULL");
    }

    public function down(): void {}
};
