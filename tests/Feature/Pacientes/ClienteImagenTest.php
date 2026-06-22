<?php

use App\Models\Cliente;
use App\Models\ClienteImagen;
use Illuminate\Support\Facades\Storage;

/**
 * Las imágenes del paciente no deben dejar archivos huérfanos: al borrar el
 * registro se elimina también el archivo del disco.
 */

it('borra el archivo del disco al eliminar la imagen', function () {
    Storage::fake('public');

    $cliente = Cliente::factory()->create();
    Storage::disk('public')->put("clientes/{$cliente->id}/foto.webp", 'contenido');

    $imagen = ClienteImagen::create([
        'cliente_id' => $cliente->id,
        'path'       => "clientes/{$cliente->id}/foto.webp",
    ]);

    Storage::disk('public')->assertExists($imagen->path);

    $imagen->delete();

    Storage::disk('public')->assertMissing("clientes/{$cliente->id}/foto.webp");
});

it('no falla si el archivo ya no existe en el disco', function () {
    Storage::fake('public');

    $cliente = Cliente::factory()->create();
    $imagen = ClienteImagen::create([
        'cliente_id' => $cliente->id,
        'path'       => "clientes/{$cliente->id}/inexistente.webp",
    ]);

    $imagen->delete();

    expect(ClienteImagen::find($imagen->id))->toBeNull();
});
