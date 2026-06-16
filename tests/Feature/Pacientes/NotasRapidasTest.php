<?php

use App\Filament\Resources\ClienteResource\Pages\EditCliente;
use App\Filament\Resources\ClienteResource\RelationManagers\ClienteNotasRelationManager;
use App\Models\Cliente;
use App\Models\ClienteNota;

/**
 * Notas rápidas como tareas: Pendiente (hecha_en NULL) o Hecha (con fecha).
 * El badge de la pestaña y de la lista cuentan las PENDIENTES, para tenerlas
 * siempre presentes. Las hechas se archivan (no se borran).
 */

it('el badge muestra el número de notas pendientes', function () {
    $cliente = Cliente::factory()->create();

    ClienteNota::factory()->count(2)->create(['cliente_id' => $cliente->id]); // pendientes
    ClienteNota::factory()->hecha()->create(['cliente_id' => $cliente->id]);  // hecha

    expect(ClienteNotasRelationManager::getBadge($cliente, EditCliente::class))->toBe('2');
});

it('sin notas pendientes, el badge no se muestra', function () {
    $cliente = Cliente::factory()->create();
    ClienteNota::factory()->hecha()->create(['cliente_id' => $cliente->id]);

    expect(ClienteNotasRelationManager::getBadge($cliente, EditCliente::class))->toBeNull();
});

it('marcar hecha archiva la nota sin borrarla (queda de historial)', function () {
    $nota = ClienteNota::factory()->create(); // pendiente

    expect($nota->esta_hecha)->toBeFalse();

    $nota->update(['hecha_en' => now()]);

    expect($nota->fresh())
        ->esta_hecha->toBeTrue()
        ->hecha_en->not->toBeNull();

    // Sigue existiendo: se archiva, no se borra.
    expect(ClienteNota::find($nota->id))->not->toBeNull();
});

it('la lista cuenta las notas pendientes por paciente', function () {
    $cliente = Cliente::factory()->create();
    ClienteNota::factory()->count(2)->create(['cliente_id' => $cliente->id]);
    ClienteNota::factory()->hecha()->create(['cliente_id' => $cliente->id]);

    $conConteo = Cliente::withCount([
        'notas as notas_pendientes_count' => fn ($q) => $q->whereNull('hecha_en'),
    ])->find($cliente->id);

    expect($conConteo->notas_pendientes_count)->toBe(2);
});

it('el filtro de notas pendientes solo trae pacientes con notas sin hacer', function () {
    $conPendientes = Cliente::factory()->create();
    ClienteNota::factory()->create(['cliente_id' => $conPendientes->id]);

    $sinPendientes = Cliente::factory()->create();
    ClienteNota::factory()->hecha()->create(['cliente_id' => $sinPendientes->id]);

    $ids = Cliente::whereHas('notas', fn ($q) => $q->whereNull('hecha_en'))->pluck('id');

    expect($ids)->toContain($conPendientes->id)
        ->not->toContain($sinPendientes->id);
});
