<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('control_diarios', function (Blueprint $table) {
            $table->enum('categoria_gasto', ['daño', 'mantenimiento', 'multa', 'otro'])
                ->nullable()
                ->default('otro')
                ->after('gasto');
        });
    }

    public function down(): void
    {
        Schema::table('control_diarios', function (Blueprint $table) {
            $table->dropColumn('categoria_gasto');
        });
    }
};
