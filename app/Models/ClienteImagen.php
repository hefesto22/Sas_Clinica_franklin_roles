<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClienteImagen extends Model
{
    use HasFactory;

    protected $table = 'cliente_imagenes';

    protected $fillable = [
        'cliente_id',
        'path',
    ];

    /**
     * Al borrar el registro se elimina también el archivo del disco, para no
     * dejar imágenes huérfanas ocupando espacio. Sirve para borrado individual
     * y en lote (el evento corre por cada modelo eliminado).
     */
    protected static function booted(): void
    {
        static::deleting(function (self $imagen): void {
            if ($imagen->path && Storage::disk('public')->exists($imagen->path)) {
                Storage::disk('public')->delete($imagen->path);
            }
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
