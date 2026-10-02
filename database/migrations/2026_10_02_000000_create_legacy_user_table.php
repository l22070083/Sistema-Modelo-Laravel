<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solo base nueva aislada. La importación de MySQL se preparará por separado.
        if (Schema::hasTable('user')) {
            return;
        }
        Schema::create('user', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('nombre');
            $table->string('apellidos')->nullable();
            $table->string('matricula', 50)->nullable();
            $table->unsignedInteger('rol_id');
            $table->smallInteger('status')->default(5);
            $table->string('password_hash');
            $table->string('auth_key', 32);
            $table->string('verification_token')->nullable();
            $table->string('password_reset_token')->nullable();
            $table->unsignedInteger('licenciatura_id')->nullable();
            $table->unsignedInteger('grupo_id')->nullable();
            $table->unsignedInteger('genero_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('No se elimina automáticamente una tabla de usuarios importada.');
        }
        Schema::dropIfExists('user');
    }
};
