<?php

use App\Livewire\Odontograma;
use App\Models\Cliente;
use App\Models\EvaluacionDetalleCondicion;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Livewire\Livewire;

/**
 * Odontograma único por paciente (notación FDI con punto, como guarda
 * producción). Se registra una condición con un clic en su chip de color;
 * cada pieza acumula un log de condiciones (nota y fechas por fila), y la
 * misma condición puede repetirse en el tiempo (recurrencia). Editar es
 * exclusivo del rol con update_evaluacion.
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

function usuarioOdontograma(string $rol): User
{
    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

it('el doctor registra una condición con un clic en el color', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.8')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();

    expect($condicion->condicion)->toBe('caries')
        ->and($condicion->tratada)->toBeFalse()
        ->and($condicion->nota)->toBeNull()
        ->and($condicion->detalle->pieza)->toBe('1.8')
        ->and($condicion->detalle->evaluacion->cliente_id)->toBe($cliente->id);
});

it('puede editar la nota de una condición ya registrada', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.8')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();

    $componente->call('editarNota', $condicion->id)
        ->set('editNota', 'Caries oclusal profunda, cara mesial')
        ->call('guardarNota');

    expect($condicion->fresh()->nota)->toBe('Caries oclusal profunda, cara mesial');
});

it('una pieza puede acumular varias condiciones distintas', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '4.5');

    foreach (['caries', 'fractura', 'extraccion_indicada'] as $condicion) {
        $componente->call('agregarCondicion', $condicion);
    }

    expect($componente->instance()->condicionesDe('4.5'))
        ->toHaveCount(3)
        ->toContain('caries')
        ->toContain('fractura')
        ->toContain('extraccion_indicada');
});

it('permite registrar la misma condición dos veces en la pieza (recurrencia)', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '4.4')
        ->call('agregarCondicion', 'caries')
        ->call('agregarCondicion', 'caries');

    expect(EvaluacionDetalleCondicion::where('condicion', 'caries')->count())->toBe(2);
});

it('marcar una sola condición como tratada no deja la pieza completa', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '4.4')
        ->call('agregarCondicion', 'caries')
        ->call('agregarCondicion', 'fractura');

    $caries = EvaluacionDetalleCondicion::where('condicion', 'caries')->first();
    $componente->call('alternarTratada', $caries->id);

    expect($componente->instance()->estadoDe('4.4'))->toBe('pendiente');
});

it('la pieza queda hecha cuando todas sus condiciones están tratadas', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '4.4')
        ->call('agregarCondicion', 'caries')
        ->call('agregarCondicion', 'fractura');

    EvaluacionDetalleCondicion::all()->each(
        fn ($condicion) => $componente->call('alternarTratada', $condicion->id)
    );

    expect($componente->instance()->estadoDe('4.4'))->toBe('hecho');
});

it('alternar tratada registra la fecha de tratamiento', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '2.4')
        ->call('agregarCondicion', 'obturacion');

    $condicion = EvaluacionDetalleCondicion::first();
    $componente->call('alternarTratada', $condicion->id);

    expect($condicion->fresh()->tratada)->toBeTrue()
        ->and($condicion->fresh()->tratada_en)->not->toBeNull();
});

it('marcar tratada permite una nota de tratamiento opcional', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();

    $componente->call('iniciarTratamiento', $condicion->id)
        ->set('notaTratamiento', 'Obturación con resina, cara oclusal')
        ->call('confirmarTratamiento');

    expect($condicion->fresh())
        ->tratada->toBeTrue()
        ->tratada_en->not->toBeNull()
        ->nota_tratamiento->toBe('Obturación con resina, cara oclusal');
});

it('marcar tratada sin nota de tratamiento también funciona', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();

    $componente->call('iniciarTratamiento', $condicion->id)
        ->call('confirmarTratamiento');

    expect($condicion->fresh())
        ->tratada->toBeTrue()
        ->nota_tratamiento->toBeNull();
});

it('volver a pendiente limpia la nota de tratamiento', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();

    $componente->call('iniciarTratamiento', $condicion->id)
        ->set('notaTratamiento', 'Resina')
        ->call('confirmarTratamiento');

    $componente->call('alternarTratada', $condicion->id); // des-marcar

    expect($condicion->fresh())
        ->tratada->toBeFalse()
        ->tratada_en->toBeNull()
        ->nota_tratamiento->toBeNull();
});

it('eliminar archiva la condición sin destruirla (soft delete)', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '3.6')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();
    $componente->call('eliminarCondicion', $condicion->id);

    expect(EvaluacionDetalleCondicion::find($condicion->id))->toBeNull()
        ->and(EvaluacionDetalleCondicion::withTrashed()->find($condicion->id))->not->toBeNull();
});

it('ignora condiciones fuera del catálogo', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'condicion_falsa');

    expect(EvaluacionDetalleCondicion::count())->toBe(0);
});

it('el asistente no puede editar desde el odontograma', function () {
    $this->actingAs(usuarioOdontograma('asistente'));
    $cliente = Cliente::factory()->create();

    Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.1')
        ->call('agregarCondicion', 'caries');

    expect(EvaluacionDetalleCondicion::count())->toBe(0);
});

it('refleja el estado de cada pieza: vacío, pendiente y hecho', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();
    $odontograma = $cliente->odontograma();

    $pendiente = $odontograma->detalles()->create(['pieza' => '1.1']);
    $pendiente->condicionesClinicas()->create([
        'condicion' => 'caries', 'tratada' => false, 'detectada_en' => now()->toDateString(),
    ]);

    $hecho = $odontograma->detalles()->create(['pieza' => '1.2']);
    $hecho->condicionesClinicas()->create([
        'condicion' => 'sellante', 'tratada' => true,
        'detectada_en' => now()->toDateString(), 'tratada_en' => now()->toDateString(),
    ]);

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente]);

    expect($componente->instance()->estadoDe('1.1'))->toBe('pendiente')
        ->and($componente->instance()->estadoDe('1.2'))->toBe('hecho')
        ->and($componente->instance()->estadoDe('4.8'))->toBe('vacio');
});
