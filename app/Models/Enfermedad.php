<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Enfermedad extends Model
{
    use HasFactory;
    protected $table = 'enfermedades';
    protected $fillable = ['nombre', 'descripcion', 'categoria', 'activa'];

    protected $casts = ['activa' => 'boolean'];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'enfermedad_producto')
            ->withPivot('instrucciones', 'orden')
            ->orderByPivot('orden')
            ->withTimestamps();
    }
}
