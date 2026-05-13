<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id');
            $table->index('user_id');
        });

        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id');
            $table->index('user_id');
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id');
            $table->index('user_id');
        });

        Schema::table('control_diarios', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('control_diarios', fn (Blueprint $table) => $table->dropIndex(['user_id']));
        Schema::table('contratos', fn (Blueprint $table) => $table->dropIndex(['user_id']));
        Schema::table('vehiculos', fn (Blueprint $table) => $table->dropIndex(['user_id']));
        Schema::table('personas', fn (Blueprint $table) => $table->dropIndex(['user_id']));

        Schema::table('control_diarios', fn (Blueprint $table) => $table->dropColumn('user_id'));
        Schema::table('contratos', fn (Blueprint $table) => $table->dropColumn('user_id'));
        Schema::table('vehiculos', fn (Blueprint $table) => $table->dropColumn('user_id'));
        Schema::table('personas', fn (Blueprint $table) => $table->dropColumn('user_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('user_id'));
    }
};
