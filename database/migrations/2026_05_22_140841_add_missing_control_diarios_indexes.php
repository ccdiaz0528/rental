<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('control_diarios', function (Blueprint $table) {
            $table->index('categoria_gasto');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('control_diarios', function (Blueprint $table) {
            $table->dropIndex(['categoria_gasto']);
            $table->dropIndex(['updated_at']);
        });
    }
};
