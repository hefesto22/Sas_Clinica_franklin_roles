<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventEspecialidad extends Model
{
    use HasFactory;

    protected $table = 'event_especialidad';

    protected $fillable = [
        'event_id',
        'especialidad_id',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class);
    }
}
