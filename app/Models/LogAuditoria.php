<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogAuditoria extends Model
{
    use HasFactory;
    protected $table = 'logs_auditoria';
    protected $fillable = ['user_id', 'accion', 'tabla_afectada', 'registro_id', 'datos_anteriores', 'datos_nuevos', 'ip', 'sucursal_id'];

    protected $casts = ['datos_anteriores' => 'array', 'datos_nuevos' => 'array'];
    public $timestamps = true;

    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
    public function sucursal() { return $this->belongsTo(Sucursal::class); }
}
