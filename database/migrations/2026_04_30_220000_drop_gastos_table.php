<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('gastos');
    }

    public function down(): void
    {
        // Legacy standalone gastos module removed. No rollback schema recreated.
    }
};
