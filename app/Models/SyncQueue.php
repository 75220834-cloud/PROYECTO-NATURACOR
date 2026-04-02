<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncQueue extends Model
{
    protected $table = 'sync_queue';
    protected $fillable = ['tabla', 'operacion', 'payload', 'sincronizado', 'intentos'];
    protected $casts = ['payload' => 'array', 'sincronizado' => 'boolean'];
}
