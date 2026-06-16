<?php

use App\Models\Cliente;

/**
 * Actividades del expediente: registro manual de lo que se le hizo al
 * paciente, con tipo opcional (general/ortodoncia) y pago opcional.
 */

it('registra una actividad con tipo y permite pago vacío', function () {
    $cliente = Cliente::factory()->create();

    $actividad = $cliente->actividades()->create([
        'fecha'     => now(),
        'actividad' => 'Limpieza general',
        'tipo'      => 'general',
        'pago'      => null,
    ]);

    expect($actividad->fresh())
        ->tipo->toBe('general')
        ->pago->toBeNull();
});

it('una actividad puede registrarse sin tipo (histórico)', function () {
    $cliente = Cliente::factory()->create();

    $actividad = $cliente->actividades()->create([
        'fecha'     => now(),
        'actividad' => 'Registro histórico sin clasificar',
    ]);

    expect($actividad->fresh()->tipo)->toBeNull();
});
