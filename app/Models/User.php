<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;


    protected $fillable = [
        'name', 'email', 'password', 'sucursal_id', 'activo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function cajaSesiones()
    {
        return $this->hasMany(CajaSesion::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
