<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ClientePadecimiento extends Model
{
    protected $table = 'cliente_padecimientos';
    protected $fillable = ['cliente_id', 'enfermedad_id', 'registrado_por'];

    public function enfermedad() {
        return $this->belongsTo(Enfermedad::class);
    }
    public function cliente() {
        return $this->belongsTo(Cliente::class);
    }
}
