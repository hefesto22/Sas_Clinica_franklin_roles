<?php

namespace App\Livewire;

use App\Models\Cliente;
use App\Models\Evaluacion;
use App\Models\EvaluacionDetalle;
use App\Models\EvaluacionDetalleCondicion;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Odontograma único por paciente (notación FDI).
 *
 * Cada pieza acumula un LOG de condiciones (EvaluacionDetalleCondicion): una
 * fila por condición, con su nota y sus fechas. La misma condición puede
 * repetirse en el tiempo sobre la misma pieza (recurrencia: el paciente
 * vuelve por caries en el mismo diente).
 *
 * UX: se registra una condición con UN CLIC en su chip de color (rápido,
 * como una ficha dental clásica). La nota y el estado "tratada" se ajustan
 * después por fila. El chart pinta el estado actual derivándolo del log.
 * Editar requiere el permiso update de EvaluacionPolicy.
 */
class Odontograma extends Component
{
    public Cliente $cliente;

    public Evaluacion $evaluacion;

    public ?string $piezaSeleccionada = null;

    /** Edición inline de la nota (detección) de una condición ya registrada. */
    public ?int $editandoId = null;

    public string $editNota = '';

    /** Marcar tratada con nota de tratamiento opcional. */
    public ?int $tratandoId = null;

    public string $notaTratamiento = '';

    /**
     * Espejo de la hoja: lo que el doctor escribió por diente en la(s)
     * hoja(s) de evaluación. pieza => ['texto' => ..., 'hecho' => bool].
     */
    public array $hoja = [];

    /** Arcadas en orden visual (notación FDI con punto, como guarda la BD). */
    public const ARCADAS = [
        'superior_permanente' => [['1.8', '1.7', '1.6', '1.5', '1.4', '1.3', '1.2', '1.1'], ['2.1', '2.2', '2.3', '2.4', '2.5', '2.6', '2.7', '2.8']],
        'superior_deciduo'    => [['5.5', '5.4', '5.3', '5.2', '5.1'], ['6.1', '6.2', '6.3', '6.4', '6.5']],
        'inferior_deciduo'    => [['8.5', '8.4', '8.3', '8.2', '8.1'], ['7.1', '7.2', '7.3', '7.4', '7.5']],
        'inferior_permanente' => [['4.8', '4.7', '4.6', '4.5', '4.4', '4.3', '4.2', '4.1'], ['3.1', '3.2', '3.3', '3.4', '3.5', '3.6', '3.7', '3.8']],
    ];

    public function mount(Cliente $cliente): void
    {
        $this->cliente = $cliente;
        $this->evaluacion = $cliente->odontograma();
        $this->cargarDetalles();
    }

    /** Carga las piezas con su log de condiciones (más reciente primero). */
    protected function cargarDetalles(): void
    {
        $this->evaluacion->load([
            'detalles.condicionesClinicas' => fn ($q) => $q
                ->orderByDesc('detectada_en')
                ->orderByDesc('id'),
        ]);

        $this->cargarHoja();
    }

    /**
     * Trae lo escrito en las HOJAS de evaluación del paciente (formato hoja,
     * texto por diente) para reflejarlo en el odontograma. Toma lo último
     * registrado por pieza. Excluye la evaluación dedicada al odontograma.
     */
    protected function cargarHoja(): void
    {
        $this->hoja = EvaluacionDetalle::query()
            ->whereHas('evaluacion', fn ($q) => $q
                ->where('cliente_id', $this->cliente->getKey())
                ->where('es_odontograma', false))
            ->whereNotNull('diagnostico')
            ->where('diagnostico', '!=', '')
            ->orderBy('id')
            ->get(['pieza', 'diagnostico', 'hecho'])
            ->groupBy('pieza')
            ->map(fn ($rows) => [
                'texto' => $rows->last()->diagnostico,
                'hecho' => (bool) $rows->last()->hecho,
            ])
            ->all();
    }

    public function hojaTextoDe(string $pieza): ?string
    {
        return $this->hoja[$pieza]['texto'] ?? null;
    }

    public function hojaHechoDe(string $pieza): bool
    {
        return (bool) ($this->hoja[$pieza]['hecho'] ?? false);
    }

    public function tieneHoja(string $pieza): bool
    {
        return isset($this->hoja[$pieza]);
    }

    public function seleccionar(string $pieza): void
    {
        if (! in_array($pieza, collect(self::ARCADAS)->flatten()->all(), true)) {
            return;
        }

        $this->piezaSeleccionada = $pieza;
        $this->cancelarNota();
        $this->cancelarTratamiento();
    }

    /** Comprueba el permiso y avisa si falta. */
    protected function autoriza(): bool
    {
        if (auth()->user()?->can('update', $this->evaluacion)) {
            return true;
        }

        Notification::make()
            ->title('Sin permiso para editar el odontograma')
            ->danger()
            ->send();

        return false;
    }

    /**
     * Registra una condición en la pieza seleccionada con un solo clic:
     * queda pendiente y detectada hoy. La nota y el "tratada" se ajustan
     * después por fila. Permite repetir la misma condición (recurrencia).
     */
    public function agregarCondicion(string $condicion): void
    {
        if (! $this->piezaSeleccionada || ! $this->autoriza()) {
            return;
        }

        // Solo condiciones del catálogo (defense in depth: el valor llega del navegador).
        if (! array_key_exists($condicion, EvaluacionDetalle::CONDICIONES)) {
            return;
        }

        $detalle = $this->evaluacion->detalles()->firstOrCreate(
            ['pieza' => $this->piezaSeleccionada],
        );

        $detalle->condicionesClinicas()->create([
            'condicion'    => $condicion,
            'tratada'      => false,
            'detectada_en' => now()->toDateString(),
        ]);

        $this->cargarDetalles();

        Notification::make()
            ->title($this->etiquetaCondicion($condicion) . " agregada a la pieza {$this->piezaSeleccionada}")
            ->success()
            ->send();
    }

    public function alternarTratada(int $condicionId): void
    {
        if (! $this->autoriza()) {
            return;
        }

        $condicion = $this->condicionPropia($condicionId);

        if (! $condicion) {
            return;
        }

        $tratada = ! $condicion->tratada;

        $condicion->update([
            'tratada'          => $tratada,
            'tratada_en'       => $tratada ? now()->toDateString() : null,
            // Al volver a pendiente, el detalle del tratamiento ya no aplica.
            'nota_tratamiento' => $tratada ? $condicion->nota_tratamiento : null,
        ]);

        $this->cargarDetalles();
    }

    /** Abre el form para marcar tratada con una nota de tratamiento opcional. */
    public function iniciarTratamiento(int $condicionId): void
    {
        if (! $this->autoriza()) {
            return;
        }

        $condicion = $this->condicionPropia($condicionId);

        if (! $condicion) {
            return;
        }

        $this->cancelarNota();
        $this->tratandoId = $condicion->id;
        $this->notaTratamiento = (string) $condicion->nota_tratamiento;
    }

    public function confirmarTratamiento(): void
    {
        if (! $this->tratandoId || ! $this->autoriza()) {
            return;
        }

        $this->validate(['notaTratamiento' => ['nullable', 'string', 'max:1000']]);

        $this->condicionPropia($this->tratandoId)?->update([
            'tratada'          => true,
            'tratada_en'       => now()->toDateString(),
            'nota_tratamiento' => trim($this->notaTratamiento) ?: null,
        ]);

        $this->cancelarTratamiento();
        $this->cargarDetalles();
    }

    public function cancelarTratamiento(): void
    {
        $this->tratandoId = null;
        $this->notaTratamiento = '';
        $this->resetValidation();
    }

    /** Fija (o limpia) el tamaño de una condición: pequena | mediana | grande | null. */
    public function cambiarTamano(int $condicionId, ?string $tamano): void
    {
        if (! $this->autoriza()) {
            return;
        }

        if ($tamano !== null && ! array_key_exists($tamano, EvaluacionDetalleCondicion::TAMANOS)) {
            return;
        }

        $this->condicionPropia($condicionId)?->update(['tamano' => $tamano]);

        $this->cargarDetalles();
    }

    /** Catálogo de tamaños para los botones del panel. */
    public function getCatalogoTamanosProperty(): array
    {
        return EvaluacionDetalleCondicion::TAMANOS;
    }

    /** Abre la edición inline de la nota de una condición. */
    public function editarNota(int $condicionId): void
    {
        if (! $this->autoriza()) {
            return;
        }

        $condicion = $this->condicionPropia($condicionId);

        if (! $condicion) {
            return;
        }

        $this->cancelarTratamiento();
        $this->editandoId = $condicion->id;
        $this->editNota = (string) $condicion->nota;
    }

    public function guardarNota(): void
    {
        if (! $this->editandoId || ! $this->autoriza()) {
            return;
        }

        $this->validate(['editNota' => ['nullable', 'string', 'max:1000']]);

        $this->condicionPropia($this->editandoId)?->update([
            'nota' => trim($this->editNota) ?: null,
        ]);

        $this->cancelarNota();
        $this->cargarDetalles();
    }

    public function cancelarNota(): void
    {
        $this->editandoId = null;
        $this->editNota = '';
        $this->resetValidation();
    }

    public function eliminarCondicion(int $condicionId): void
    {
        if (! $this->autoriza()) {
            return;
        }

        $condicion = $this->condicionPropia($condicionId);

        if (! $condicion) {
            return;
        }

        // Una condición ya tratada es historia clínica: no se archiva mientras
        // siga marcada como hecha. Hay que volverla a pendiente primero.
        if ($condicion->tratada) {
            Notification::make()
                ->title('No se puede archivar una condición ya hecha')
                ->body('Primero marcala como no hecha y luego podés archivarla.')
                ->warning()
                ->send();

            return;
        }

        // Soft delete: el registro clínico se archiva, no se destruye.
        $condicion->delete();

        $this->cargarDetalles();
    }

    /**
     * Recupera una condición SOLO si pertenece al odontograma de este
     * paciente. Defense in depth: no confiar en el id que llega del navegador.
     */
    protected function condicionPropia(int $condicionId): ?EvaluacionDetalleCondicion
    {
        return EvaluacionDetalleCondicion::query()
            ->whereHas('detalle', fn ($q) => $q->where('evaluacion_id', $this->evaluacion->id))
            ->find($condicionId);
    }

    // ──────────────────────────────────────────────────────────────────
    //  Helpers de lectura para el chart (derivan del log de condiciones)
    // ──────────────────────────────────────────────────────────────────

    /** Filas de condiciones de una pieza (colección, ya cargada). */
    public function condicionesRowsDe(string $pieza): Collection
    {
        $detalle = $this->evaluacion->detalles->firstWhere('pieza', $pieza);

        // Pendientes primero, tratadas al final (orden estable: mantiene el id dentro de cada grupo).
        return ($detalle?->condicionesClinicas ?? collect())
            ->sortBy(fn ($c) => $c->tratada ? 1 : 0)
            ->values();
    }

    /** @return array<string> Condiciones presentes en la pieza (distintas). */
    public function condicionesDe(string $pieza): array
    {
        return $this->condicionesRowsDe($pieza)
            ->pluck('condicion')
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<string> Condiciones cuyas ocurrencias están todas tratadas. */
    public function tratadasDe(string $pieza): array
    {
        return $this->condicionesRowsDe($pieza)
            ->groupBy('condicion')
            ->filter(fn (Collection $rows) => $rows->every(fn ($r) => $r->tratada))
            ->keys()
            ->all();
    }

    /** Estado visual de la pieza: vacio | pendiente | hecho. */
    public function estadoDe(string $pieza): string
    {
        $rows = $this->condicionesRowsDe($pieza);
        $tieneHoja = $this->tieneHoja($pieza);

        if ($rows->isEmpty() && ! $tieneHoja) {
            return 'vacio';
        }

        $condicionesHechas = $rows->isEmpty() || $rows->every(fn ($r) => $r->tratada);
        $hojaHecha = ! $tieneHoja || $this->hojaHechoDe($pieza);

        return ($condicionesHechas && $hojaHecha) ? 'hecho' : 'pendiente';
    }

    /** Notas de la pieza para el tooltip: lo de la hoja + las notas de condiciones. */
    public function diagnosticoDe(string $pieza): ?string
    {
        $partes = collect();

        if ($texto = $this->hojaTextoDe($pieza)) {
            $partes->push('Hoja: ' . $texto);
        }

        $this->condicionesRowsDe($pieza)
            ->pluck('nota')
            ->filter()
            ->unique()
            ->each(fn ($n) => $partes->push($n));

        return $partes->isEmpty() ? null : $partes->implode(' · ');
    }

    public function tieneCondicion(string $pieza, string $condicion): bool
    {
        return in_array($condicion, $this->condicionesDe($pieza), true);
    }

    /** Color principal de la corona: primera condición con color. */
    public function colorDe(string $pieza): ?string
    {
        foreach ($this->condicionesDe($pieza) as $condicion) {
            $color = EvaluacionDetalle::CONDICIONES[$condicion]['color'] ?? null;

            if ($color !== null) {
                return $color;
            }
        }

        // Sin condición, pero con texto en la hoja: marca neutra (slate).
        return $this->tieneHoja($pieza) ? '#94a3b8' : null;
    }

    /** Colores secundarios (condiciones adicionales) para los puntos. */
    public function coloresExtraDe(string $pieza): array
    {
        return collect($this->condicionesDe($pieza))
            ->map(fn ($c) => EvaluacionDetalle::CONDICIONES[$c]['color'] ?? null)
            ->filter()
            ->values()
            ->slice(1, 3)
            ->all();
    }

    /** Etiqueta legible de una condición. */
    public function etiquetaCondicion(string $condicion): string
    {
        return EvaluacionDetalle::CONDICIONES[$condicion]['label'] ?? $condicion;
    }

    /** Tipo anatómico según posición FDI: incisivo | canino | premolar | molar. */
    public function tipoDe(string $pieza): string
    {
        [$cuadrante, $posicion] = explode('.', $pieza);
        $esDeciduo = (int) $cuadrante >= 5;

        return match (true) {
            (int) $posicion <= 2 => 'incisivo',
            (int) $posicion === 3 => 'canino',
            $esDeciduo => 'molar', // deciduos 4-5 son molares
            (int) $posicion <= 5 => 'premolar',
            default => 'molar',
        };
    }

    public function getPuedeEditarProperty(): bool
    {
        return auth()->user()?->can('update', $this->evaluacion) ?? false;
    }

    /** Catálogo de condiciones para los chips del formulario. */
    public function getCatalogoCondicionesProperty(): array
    {
        return EvaluacionDetalle::CONDICIONES;
    }

    public function render()
    {
        return view('livewire.odontograma');
    }
}
