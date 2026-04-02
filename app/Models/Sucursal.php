<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sucursal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sucursales';
    protected $fillable = ['nombre', 'direccion', 'telefono', 'ruc', 'activa'];
    protected $casts = ['activa' => 'boolean'];


    public function usuarios() { return $this->hasMany(User::class); }
    public function productos() { return $this->hasMany(Producto::class); }
    public function ventas() { return $this->hasMany(Venta::class); }
    public function cajaSesiones() { return $this->hasMany(CajaSesion::class); }
}
