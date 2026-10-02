<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    public const ADMIN = 1;

    public const COORDINADOR = 2;

    public const ALUMNO = 3;

    public const ACTIVE = 10;

    public const PENDING = 5;

    public const INACTIVE = 0;

    protected $table = 'user';

    protected $guarded = ['id', 'rol_id', 'status', 'password_hash', 'auth_key', 'verification_token', 'password_reset_token'];

    protected $hidden = ['password_hash', 'auth_key', 'verification_token', 'password_reset_token'];

    protected $authPasswordName = 'password_hash';

    // Yii no tiene remember_token: se utilizan sesiones sin recordar usuario.
    protected $rememberTokenName = '';

    protected function casts(): array
    {
        return ['rol_id' => 'integer', 'status' => 'integer'];
    }

    public function scopePendientes(Builder $query): void
    {
        $query->where('rol_id', self::ALUMNO)->where(function (Builder $q) {
            $q->where('status', self::PENDING)->orWhere(function (Builder $legacy) {
                $legacy->where('status', self::INACTIVE)->whereNotNull('verification_token');
            });
        });
    }
}
