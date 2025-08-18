<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventServicio extends Model
{
    use HasFactory;

    protected $table = 'event_servicio';

    protected $fillable = [
        'event_id',
        'servicio_id',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function servicio()
    {
        return $this->belongsTo(ServicioEspecialidad::class);
    }
}
