<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('control_diarios', function (Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
            $table->dropUnique(['vehiculo_id', 'fecha']);
            $table->unsignedBigInteger('vehiculo_id')->nullable()->change();
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->nullOnDelete();
            $table->unique(['vehiculo_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('control_diarios', function (Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
            $table->dropUnique(['vehiculo_id', 'fecha']);
            $table->unsignedBigInteger('vehiculo_id')->nullable(false)->change();
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->cascadeOnDelete();
            $table->unique(['vehiculo_id', 'fecha']);
        });
    }
};
