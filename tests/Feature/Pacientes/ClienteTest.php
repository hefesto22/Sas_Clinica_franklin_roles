<?php

use App\Models\Cliente;
use Illuminate\Database\QueryException;

/**
 * Reglas del expediente del paciente: DNI opcional (algunos llegan sin
 * identidad y se completa después) pero único cuando está presente, y
 * tipo de paciente (general u ortodoncia).
 */

it('calcula la edad a partir de la fecha de nacimiento', function () {
    $cliente = Cliente::factory()->create([
        'fecha_nacimiento' => now()->subYears(30)->subDays(5)->toDateString(),
    ]);

    expect($cliente->edad)->toBe(30);
});

it('la edad es null si no hay fecha de nacimiento', function () {
    $cliente = Cliente::factory()->create(['fecha_nacimiento' => null]);

    expect($cliente->edad)->toBeNull();
});

it('permite guardar un paciente sin DNI', function () {
    $cliente = Cliente::factory()->create(['dni' => null]);

    expect($cliente->fresh()->dni)->toBeNull();
});

it('permite varios pacientes sin DNI sin que choquen', function () {
    Cliente::factory()->create(['dni' => null]);
    Cliente::factory()->create(['dni' => null]);

    expect(Cliente::whereNull('dni')->count())->toBe(2);
});

it('mantiene el DNI único cuando está presente', function () {
    Cliente::factory()->create(['dni' => '0801-1990-12345']);

    expect(fn () => Cliente::factory()->create(['dni' => '0801-1990-12345']))
        ->toThrow(QueryException::class);
});

it('guarda el tipo de paciente general u ortodoncia', function () {
    $ortodoncia = Cliente::factory()->create(['tipo_paciente' => 'ortodoncia']);
    $general    = Cliente::factory()->create(['tipo_paciente' => 'general']);

    expect($ortodoncia->fresh()->tipo_paciente)->toBe('ortodoncia')
        ->and($general->fresh()->tipo_paciente)->toBe('general');
});

it('el tipo de paciente por defecto es general', function () {
    // Sin especificar tipo_paciente, la columna usa su default.
    $cliente = Cliente::create([
        'nombre'     => 'Paciente Sin Tipo',
        'created_by' => \App\Models\User::factory()->create()->id,
    ]);

    expect($cliente->fresh()->tipo_paciente)->toBe('general');
});

it('detecta un nombre exactamente igual sin distinguir mayúsculas', function () {
    Cliente::factory()->create(['nombre' => 'Mauricio Cruz']);

    // La collation utf8mb4_unicode_ci hace la comparación case-insensitive.
    $existe = Cliente::where('nombre', 'mauricio cruz')->exists();

    expect($existe)->toBeTrue();
});

it('no considera duplicado un nombre distinto aunque comparta partes', function () {
    Cliente::factory()->create(['nombre' => 'Mauricio Cruz']);

    expect(Cliente::where('nombre', 'Mauricio Orlando Cruz')->exists())->toBeFalse();
});
