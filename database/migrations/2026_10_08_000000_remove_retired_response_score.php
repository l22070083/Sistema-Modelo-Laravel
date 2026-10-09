<?php

use App\Support\RetiredModulesSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        RetiredModulesSchema::apply(DB::connection()->getPdo());
    }

    public function down(): void
    {
        throw new RuntimeException('La valoración retirada requiere restaurar el respaldo previo.');
    }
};
