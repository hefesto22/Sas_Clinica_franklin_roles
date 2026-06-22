<?php

namespace App\Livewire;

use App\Models\Evaluacion;
use App\Models\EvaluacionDetalle;
use App\Models\EvaluacionDetalleCondicion;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Hoja de diagnóstico interactiva (formato de hoja, notación FDI).
 *
 * La grilla queda compacta: cada diente muestra su texto y un punto de color
 * si tiene condición. Al hacer clic en un diente se abre un panelcito para:
 *  - cargar su texto + "Hecho" (se guardan en la hoja), y
 *  - agregar VARIAS condiciones (cada una con su tamaño).
 *
 * Las condiciones se registran en el odontograma del paciente, marcadas con
 * la hoja de origen, así un diente puede acumular distintos tratamientos.
 */
class HojaDiagnostico extends Component
{
    public Evaluacion $hoja;

    public ?string $piezaSeleccionada = null;

    public string $texto = '';

    public bool $hecho = false;

    /** Nota propia de cada condición del diente, indexada por su id. */
    public array $notasCondicion = [];

    /** Si se muestran todas las condiciones del diente o solo las primeras. */
    public bool $verTodas = false;

    /** Cuántas condiciones se muestran antes del botón "ver más". */
    public const TOPE_CONDICIONES = 3;

    /** Layout FDI: filas de 4; null = espacio vacío. */
    public const LAYOUT = [
        ['1.8', null, null, '2.8'],
        ['1.7', null, null, '2.7'],
        ['1.6', null, null, '2.6'],
        ['1.5', '5.5', '2.5', '6.5'],
        ['1.4', '5.4', '2.4', '6.4'],
        ['1.3', '5.3', '2.3', '6.3'],
        ['1.2', '5.2', '2.2', '6.2'],
        ['1.1', '5.1', '2.1', '6.1'],
        // Arcada inferior: izquierda = cuadrante 4 (lado derecho del paciente),
        // derecha = cuadrante 3, como en una ficha dental vista de frente.
        ['4.1', '8.1', '3.1', '7.1'],
        ['4.2', '8.2', '3.2', '7.2'],
        ['4.3', '8.3', '3.3', '7.3'],
        ['4.4', '8.4', '3.4', '7.4'],
        ['4.5', '8.5', '3.5', '7.5'],
        ['4.6', null, null, '3.6'],
        ['4.7', null, null, '3.7'],
        ['4.8', null, null, '3.8'],
    ];

    public function mount(Evaluacion $hoja): void
    {
        $this->hoja = $hoja->load('detalles');
    }

    protected function cliente()
    {
        return $this->hoja->cliente;
    }

    /** Odontograma único del paciente (donde viven las condiciones). */
    protected function odontograma(): Evaluacion
    {
        return $this->cliente()->odontograma();
    }

    public function seleccionar(string $pieza): void
    {
        if (! $this->esPiezaValida($pieza)) {
            return;
        }

        $this->piezaSeleccionada = $pieza;
        $this->verTodas = false;
        $detalle = $this->hoja->detalles->firstWhere('pieza', $pieza);
        $this->texto = $detalle?->diagnostico ?? '';
        $this->hecho = (bool) ($detalle?->hecho ?? false);
        $this->cargarNotasCondicion();
        $this->resetValidation();
    }

    public function cerrarPanel(): void
    {
        $this->reset(['piezaSeleccionada', 'texto', 'hecho', 'notasCondicion', 'verTodas']);
        $this->resetValidation();
    }

    public function toggleVerTodas(): void
    {
        $this->verTodas = ! $this->verTodas;
    }

    /** Sincroniza en memoria las notas de las condiciones del diente. */
    protected function cargarNotasCondicion(): void
    {
        $this->notasCondicion = $this->piezaSeleccionada
            ? $this->condicionesDe($this->piezaSeleccionada)
                ->mapWithKeys(fn ($c) => [$c->id => $c->nota ?? ''])
                ->all()
            : [];
    }

    /** Guarda la nota propia de una condición del diente (opcional). */
    public function guardarNotaCondicion(int $condicionId): void
    {
        if (! $this->puedeEditar) {
            return;
        }

        $nota = trim((string) ($this->notasCondicion[$condicionId] ?? ''));
        $this->condicionPropia($condicionId)?->update(['nota' => $nota ?: null]);

        Notification::make()->title('Nota guardada')->success()->send();
    }

    /** Guarda el texto y "Hecho" del diente en la hoja. */
    public function guardarNota(): void
    {
        if (! $this->piezaSeleccionada || ! $this->puedeEditar) {
            return;
        }

        $this->validate(['texto' => ['nullable', 'string', 'max:1000']]);

        $texto = trim($this->texto);

        if ($texto === '' && ! $this->hecho) {
            $this->hoja->detalles()->where('pieza', $this->piezaSeleccionada)->delete();
        } else {
            $this->hoja->detalles()->updateOrCreate(
                ['pieza' => $this->piezaSeleccionada],
                ['diagnostico' => $texto ?: null, 'hecho' => $this->hecho],
            );
        }

        $this->hoja->load('detalles');
        Notification::make()->title('Nota guardada')->success()->send();
    }

    /** Agrega una condición (con un clic) al diente, en el odontograma. */
    public function agregarCondicion(string $condicion): void
    {
        if (! $this->piezaSeleccionada || ! $this->puedeEditar) {
            return;
        }

        if (! array_key_exists($condicion, EvaluacionDetalle::CONDICIONES)) {
            return;
        }

        $detalle = $this->odontograma()->detalles()->firstOrCreate(['pieza' => $this->piezaSeleccionada]);

        $detalle->condicionesClinicas()->create([
            'condicion'            => $condicion,
            'detectada_en'         => $this->hoja->fecha ?: now()->toDateString(),
            'origen_evaluacion_id' => $this->hoja->getKey(),
        ]);

        $this->cargarNotasCondicion();
    }

    public function cambiarTamano(int $condicionId, ?string $tamano): void
    {
        if (! $this->puedeEditar) {
            return;
        }

        if ($tamano !== null && ! array_key_exists($tamano, EvaluacionDetalleCondicion::TAMANOS)) {
            return;
        }

        $this->condicionPropia($condicionId)?->update(['tamano' => $tamano]);
    }

    public function eliminarCondicion(int $condicionId): void
    {
        if (! $this->puedeEditar) {
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

        $condicion->delete();
        unset($this->notasCondicion[$condicionId]);
    }

    /** Marca/desmarca la condición como tratada (ya realizada), con su fecha. */
    public function alternarTratada(int $condicionId): void
    {
        if (! $this->puedeEditar) {
            return;
        }

        $condicion = $this->condicionPropia($condicionId);

        if (! $condicion) {
            return;
        }

        $condicion->update($condicion->tratada
            ? ['tratada' => false, 'tratada_en' => null]
            : ['tratada' => true, 'tratada_en' => now()->toDateString()]);
    }

    /** Una condición de este paciente aportada por esta hoja (defense in depth). */
    protected function condicionPropia(int $id): ?EvaluacionDetalleCondicion
    {
        return EvaluacionDetalleCondicion::query()
            ->where('origen_evaluacion_id', $this->hoja->getKey())
            ->whereHas('detalle', fn ($q) => $q->where('evaluacion_id', $this->odontograma()->getKey()))
            ->find($id);
    }

    /** Condiciones que esta hoja registró para una pieza. */
    public function condicionesDe(string $pieza): Collection
    {
        return EvaluacionDetalleCondicion::query()
            ->where('origen_evaluacion_id', $this->hoja->getKey())
            ->whereHas('detalle', fn ($q) => $q
                ->where('evaluacion_id', $this->odontograma()->getKey())
                ->where('pieza', $pieza))
            ->orderBy('tratada')   // pendientes primero, hechas al final
            ->orderBy('id')
            ->get();
    }

    // ── Helpers para la grilla ─────────────────────────────────────────

    public function textoDe(string $pieza): ?string
    {
        return $this->hoja->detalles->firstWhere('pieza', $pieza)?->diagnostico;
    }

    public function hechoDe(string $pieza): bool
    {
        return (bool) ($this->hoja->detalles->firstWhere('pieza', $pieza)?->hecho ?? false);
    }

    /** Color del punto: la primera condición con color, o gris si solo hay texto. */
    public function colorDe(string $pieza): ?string
    {
        foreach ($this->condicionesDe($pieza) as $c) {
            $color = EvaluacionDetalle::CONDICIONES[$c->condicion]['color'] ?? null;
            if ($color !== null) {
                return $color;
            }
        }

        return filled($this->textoDe($pieza)) ? '#94a3b8' : null;
    }

    public function etiquetaCondicion(string $condicion): string
    {
        return EvaluacionDetalle::CONDICIONES[$condicion]['label'] ?? $condicion;
    }

    public function colorCondicion(string $condicion): ?string
    {
        return EvaluacionDetalle::CONDICIONES[$condicion]['color'] ?? null;
    }

    protected function esPiezaValida(string $pieza): bool
    {
        return in_array($pieza, collect(self::LAYOUT)->flatten()->filter()->all(), true);
    }

    public function getPuedeEditarProperty(): bool
    {
        return auth()->user()?->can('update', $this->hoja) ?? false;
    }

    public function getCatalogoProperty(): array
    {
        return EvaluacionDetalle::CONDICIONES;
    }

    public function getTamanosProperty(): array
    {
        return EvaluacionDetalleCondicion::TAMANOS;
    }

    public function render()
    {
        return view('livewire.hoja-diagnostico');
    }
}
