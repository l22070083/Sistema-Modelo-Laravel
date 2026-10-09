<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('expediente_alumno', 'ocupacion')) {
            Schema::table('expediente_alumno', fn (Blueprint $table) => $table->dropColumn('ocupacion'));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('La ocupación retirada requiere restaurar el respaldo previo.');
    }
};
