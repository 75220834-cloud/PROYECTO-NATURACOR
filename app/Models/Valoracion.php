<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Valoracion extends Model
{
    use HasFactory;

    protected $table = 'valoraciones';

    protected $fillable = ['producto_id', 'nombre_cliente', 'estrellas', 'comentario', 'aprobada'];

    protected $casts = ['aprobada' => 'boolean'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
