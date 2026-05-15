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
            $table->decimal('administracion', 10, 2)->nullable()->after('gasto');
        });
    }

    public function down(): void
    {
        Schema::table('control_diarios', function (Blueprint $table) {
            $table->dropColumn('administracion');
        });
    }
};
