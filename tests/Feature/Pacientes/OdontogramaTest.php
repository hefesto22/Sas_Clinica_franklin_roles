<?php

use App\Livewire\Odontograma;
use App\Models\Cliente;
use App\Models\Evaluacion;
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

it('la hoja del paciente es única y estable (firstOrCreate)', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $hoja = $cliente->hoja();

    expect($hoja->es_odontograma)->toBeFalse()
        ->and($cliente->fresh()->hoja()->id)->toBe($hoja->id)   // siempre la misma
        ->and($hoja->id)->not->toBe($cliente->odontograma()->id); // distinta del odontograma
});

it('el odontograma es una evaluación dedicada y estable aunque existan hojas', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    // Hojas por visita: NO son el odontograma.
    Evaluacion::factory()->count(2)->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);

    $odontograma = $cliente->odontograma();

    // Crear otra hoja después no cambia el contenedor del odontograma.
    Evaluacion::factory()->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);

    expect($odontograma->es_odontograma)->toBeTrue()
        ->and($cliente->fresh()->odontograma()->id)->toBe($odontograma->id);
});

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

it('no permite archivar una condición ya tratada', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();
    $componente->call('alternarTratada', $condicion->id); // queda tratada

    // Intentar archivarla no la borra mientras esté tratada.
    $componente->call('eliminarCondicion', $condicion->id);
    expect(EvaluacionDetalleCondicion::find($condicion->id))->not->toBeNull();

    // Al volverla a pendiente, sí se puede archivar.
    $componente->call('alternarTratada', $condicion->id);
    $componente->call('eliminarCondicion', $condicion->id);
    expect(EvaluacionDetalleCondicion::find($condicion->id))->toBeNull();
});

it('desde la hoja tampoco se archiva una condición ya hecha', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();
    $hoja = Evaluacion::factory()->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);

    $componente = Livewire::test(\App\Livewire\HojaDiagnostico::class, ['hoja' => $hoja])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();
    $componente->call('alternarTratada', $condicion->id);

    $componente->call('eliminarCondicion', $condicion->id);
    expect(EvaluacionDetalleCondicion::find($condicion->id))->not->toBeNull();

    $componente->call('alternarTratada', $condicion->id);
    $componente->call('eliminarCondicion', $condicion->id);
    expect(EvaluacionDetalleCondicion::find($condicion->id))->toBeNull();
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

it('permite fijar y limpiar el tamaño de una condición', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'obturacion');

    $condicion = EvaluacionDetalleCondicion::first();

    $componente->call('cambiarTamano', $condicion->id, 'grande');
    expect($condicion->fresh()->tamano)->toBe('grande');

    $componente->call('cambiarTamano', $condicion->id, null);
    expect($condicion->fresh()->tamano)->toBeNull();
});

it('ignora un tamaño fuera del catálogo', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();
    $componente->call('cambiarTamano', $condicion->id, 'gigante');

    expect($condicion->fresh()->tamano)->toBeNull();
});

it('refleja en el odontograma lo escrito en la hoja por diente', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    // Una hoja de evaluación (no la dedicada al odontograma) con texto en 1.8.
    $hoja = Evaluacion::factory()->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);
    $hoja->detalles()->create(['pieza' => '1.8', 'diagnostico' => 'nada', 'hecho' => false]);

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente]);

    expect($componente->instance()->tieneHoja('1.8'))->toBeTrue()
        ->and($componente->instance()->hojaTextoDe('1.8'))->toBe('nada')
        ->and($componente->instance()->estadoDe('1.8'))->toBe('pendiente')
        ->and($componente->instance()->colorDe('1.8'))->toBe('#94a3b8');
});

it('la pieza con anotación de hoja marcada Hecho queda en estado hecho', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();

    $hoja = Evaluacion::factory()->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);
    $hoja->detalles()->create(['pieza' => '2.6', 'diagnostico' => 'C1', 'hecho' => true]);

    $componente = Livewire::test(Odontograma::class, ['cliente' => $cliente]);

    expect($componente->instance()->estadoDe('2.6'))->toBe('hecho');
});

it('la condición agregada desde la hoja aparece en el odontograma con su tamaño', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();
    $hoja = Evaluacion::factory()->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);

    // El doctor, en la hoja, agrega al diente 1.6: Obturación, Grande.
    Livewire::test(\App\Livewire\HojaDiagnostico::class, ['hoja' => $hoja])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'obturacion');

    $condicion = EvaluacionDetalleCondicion::first();
    expect($condicion->origen_evaluacion_id)->toBe($hoja->id)
        ->and($condicion->detalle->evaluacion->es_odontograma)->toBeTrue();

    Livewire::test(\App\Livewire\HojaDiagnostico::class, ['hoja' => $hoja])
        ->call('cambiarTamano', $condicion->id, 'grande');

    expect($condicion->fresh()->tamano)->toBe('grande');

    // El odontograma del paciente lo muestra como condición real (obturación = azul).
    $odo = Livewire::test(Odontograma::class, ['cliente' => $cliente]);
    expect($odo->instance()->colorDe('1.6'))->toBe('#3b82f6')
        ->and($odo->instance()->condicionesDe('1.6'))->toContain('obturacion');
});

it('cada condición de la hoja puede llevar su propia nota', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();
    $hoja = Evaluacion::factory()->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);

    $componente = Livewire::test(\App\Livewire\HojaDiagnostico::class, ['hoja' => $hoja])
        ->call('seleccionar', '1.8')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();

    $componente->set("notasCondicion.{$condicion->id}", 'Cara oclusal, profunda')
        ->call('guardarNotaCondicion', $condicion->id);

    expect($condicion->fresh()->nota)->toBe('Cara oclusal, profunda');
});

it('desde la hoja se marca una condición como hecha y queda con fecha', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();
    $hoja = Evaluacion::factory()->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);

    $componente = Livewire::test(\App\Livewire\HojaDiagnostico::class, ['hoja' => $hoja])
        ->call('seleccionar', '1.8')
        ->call('agregarCondicion', 'caries');

    $condicion = EvaluacionDetalleCondicion::first();

    $componente->call('alternarTratada', $condicion->id);
    expect($condicion->fresh())->tratada->toBeTrue()
        ->and($condicion->fresh()->tratada_en)->not->toBeNull();

    // El odontograma del paciente refleja la pieza como hecha.
    $odo = Livewire::test(Odontograma::class, ['cliente' => $cliente]);
    expect($odo->instance()->estadoDe('1.8'))->toBe('hecho');

    $componente->call('alternarTratada', $condicion->id);
    expect($condicion->fresh())->tratada->toBeFalse()
        ->and($condicion->fresh()->tratada_en)->toBeNull();
});

it('un solo diente acumula varias condiciones distintas desde la hoja', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();
    $hoja = Evaluacion::factory()->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);

    Livewire::test(\App\Livewire\HojaDiagnostico::class, ['hoja' => $hoja])
        ->call('seleccionar', '1.6')
        ->call('agregarCondicion', 'caries')
        ->call('agregarCondicion', 'corona');

    $odo = Livewire::test(Odontograma::class, ['cliente' => $cliente]);

    expect($odo->instance()->condicionesDe('1.6'))
        ->toHaveCount(2)
        ->toContain('caries')
        ->toContain('corona');
});

it('la hoja de diagnóstico guarda texto y hecho por diente', function () {
    $this->actingAs(usuarioOdontograma('doctor'));
    $cliente = Cliente::factory()->create();
    $hoja = Evaluacion::factory()->create(['cliente_id' => $cliente->id, 'es_odontograma' => false]);

    Livewire::test(\App\Livewire\HojaDiagnostico::class, ['hoja' => $hoja])
        ->call('seleccionar', '1.6')
        ->set('texto', 'C1')
        ->set('hecho', true)
        ->call('guardarNota');

    $detalle = $hoja->detalles()->where('pieza', '1.6')->first();

    expect($detalle->diagnostico)->toBe('C1')
        ->and($detalle->hecho)->toBeTrue();

    // El odontograma refleja el texto de la hoja (marca neutra).
    $odo = Livewire::test(Odontograma::class, ['cliente' => $cliente]);
    expect($odo->instance()->hojaTextoDe('1.6'))->toBe('C1');
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
