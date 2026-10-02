<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (config('database.default') === 'mysql' && config('database.connections.mysql.database') === 'tutoriasmv') {
            throw new \RuntimeException('Laravel no debe conectarse a la base original de Yii. Usa la copia aislada.');
        }
        Paginator::useBootstrapFive();
    }
}
