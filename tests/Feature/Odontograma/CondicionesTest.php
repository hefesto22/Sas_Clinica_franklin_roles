<?php

use App\Models\EvaluacionDetalle;
use App\Models\EvaluacionDetalleCondicion;

/**
 * Base del odontograma normalizado: nota por condición, recurrencia de la
 * misma condición en el tiempo y derivación del estado "tratada" de la pieza.
 */

it('fabrica una condición de pieza sin errores', function () {
    expect(EvaluacionDetalleCondicion::factory()->create())
        ->toBeInstanceOf(EvaluacionDetalleCondicion::class);
});

it('guarda una nota independiente por cada condición de la misma pieza', function () {
    $pieza = EvaluacionDetalle::factory()->create();

    $caries = EvaluacionDetalleCondicion::factory()
        ->condicion('caries')
        ->for($pieza, 'detalle')
        ->create(['nota' => 'Caries mesial profunda']);

    $extraccion = EvaluacionDetalleCondicion::factory()
        ->condicion('extraccion_indicada')
        ->for($pieza, 'detalle')
        ->create(['nota' => 'Extracción por fractura radicular']);

    expect($pieza->condicionesClinicas()->count())->toBe(2)
        ->and($caries->nota)->toBe('Caries mesial profunda')
        ->and($extraccion->nota)->toBe('Extracción por fractura radicular');
});

it('permite la misma condición repetida en el tiempo sobre la misma pieza', function () {
    $pieza = EvaluacionDetalle::factory()->create();

    EvaluacionDetalleCondicion::factory()
        ->condicion('caries')
        ->tratada()
        ->for($pieza, 'detalle')
        ->create(['detectada_en' => '2026-01-10', 'tratada_en' => '2026-01-10']);

    EvaluacionDetalleCondicion::factory()
        ->condicion('caries')
        ->for($pieza, 'detalle')
        ->create(['detectada_en' => '2026-06-12']);

    expect($pieza->condicionesClinicas()->where('condicion', 'caries')->count())->toBe(2);
});

it('la pieza está tratada solo si todas sus condiciones lo están', function () {
    $pieza = EvaluacionDetalle::factory()->create();

    EvaluacionDetalleCondicion::factory()->condicion('caries')->tratada()->for($pieza, 'detalle')->create();
    $pendiente = EvaluacionDetalleCondicion::factory()->condicion('endodoncia')->for($pieza, 'detalle')->create();

    expect($pieza->fresh()->estaTratada)->toBeFalse();

    $pendiente->update(['tratada' => true, 'tratada_en' => now()->toDateString()]);

    expect($pieza->fresh()->estaTratada)->toBeTrue();
});

it('una pieza sin condiciones registradas no se considera tratada', function () {
    $pieza = EvaluacionDetalle::factory()->create();

    expect($pieza->estaTratada)->toBeFalse();
});
