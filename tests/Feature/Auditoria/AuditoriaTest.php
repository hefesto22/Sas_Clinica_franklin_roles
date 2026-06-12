<?php

use App\Models\Cliente;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

function superAdmin(): User
{
    Role::findOrCreate('super_admin', 'web');

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

it('audita la edición de un cliente con el diff y el usuario causante', function () {
    $editor  = User::factory()->create();
    $cliente = Cliente::factory()->create();

    $this->actingAs($editor);
    $cliente->update(['telefono' => '9999-0000']);

    $registro = Activity::query()
        ->where('subject_type', Cliente::class)
        ->where('subject_id', $cliente->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($registro)->not->toBeNull()
        ->and($registro->causer_id)->toBe($editor->id)
        ->and($registro->attribute_changes['attributes']['telefono'])->toBe('9999-0000')
        ->and($registro->log_name)->toBe('clinico');
});

it('audita la creación y eliminación de citas', function () {
    $cita = Event::factory()->create();

    expect(Activity::where('subject_type', Event::class)
        ->where('subject_id', $cita->id)
        ->where('event', 'created')->exists())->toBeTrue();

    $id = $cita->id;
    $cita->delete();

    expect(Activity::where('subject_type', Event::class)
        ->where('subject_id', $id)
        ->where('event', 'deleted')->exists())->toBeTrue();
});

it('registra quién consultó el expediente de un paciente', function () {
    $admin   = superAdmin();
    $cliente = Cliente::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/clientes/{$cliente->id}/edit")
        ->assertOk();

    $lectura = Activity::query()
        ->where('log_name', 'expediente')
        ->where('subject_type', Cliente::class)
        ->where('subject_id', $cliente->id)
        ->where('event', 'consulta')
        ->first();

    expect($lectura)->not->toBeNull()
        ->and($lectura->causer_id)->toBe($admin->id);
});

it('solo super_admin puede ver la auditoría', function () {
    $normal = User::factory()->create();
    $admin  = superAdmin();

    expect(Gate::forUser($normal)->allows('viewAny', Activity::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('viewAny', Activity::class))->toBeTrue();
});

it('nadie puede modificar ni borrar registros de auditoría vía policy directa', function () {
    $normal = User::factory()->create();

    $registro = activity('clinico')->log('registro de prueba');

    expect(Gate::forUser($normal)->allows('update', $registro))->toBeFalse()
        ->and(Gate::forUser($normal)->allows('delete', $registro))->toBeFalse();
});
