<?php

namespace App\Livewire;

use App\Models\Evaluacion;
use Filament\Notifications\Notification;
use Livewire\Component;

/**
 * Odontograma interactivo por evaluación (notación FDI).
 *
 * Pinta las arcadas con el estado de cada pieza según EvaluacionDetalle:
 * sin registro / con diagnóstico pendiente / tratado (hecho). Clic en un
 * diente para ver/editar su diagnóstico. Editar requiere el permiso
 * update de EvaluacionPolicy (doctor sí; asistente solo consulta).
 */
class Odontograma extends Component
{
    public Evaluacion $evaluacion;

    public ?string $piezaSeleccionada = null;

    /** @var array<string> Condiciones de la pieza seleccionada (multi). */
    public array $condiciones = [];

    /** @var array<string> Condiciones ya tratadas (subconjunto de las marcadas). */
    public array $tratadas = [];

    public string $diagnostico = '';

    /** Tratamiento global: solo aplica cuando la pieza tiene nota sin condiciones. */
    public bool $hecho = false;

    /** Arcadas en orden visual (notación FDI con punto, como guarda la BD). */
    public const ARCADAS = [
        'superior_permanente' => [['1.8', '1.7', '1.6', '1.5', '1.4', '1.3', '1.2', '1.1'], ['2.1', '2.2', '2.3', '2.4', '2.5', '2.6', '2.7', '2.8']],
        'superior_deciduo'    => [['5.5', '5.4', '5.3', '5.2', '5.1'], ['6.1', '6.2', '6.3', '6.4', '6.5']],
        'inferior_deciduo'    => [['8.5', '8.4', '8.3', '8.2', '8.1'], ['7.1', '7.2', '7.3', '7.4', '7.5']],
        'inferior_permanente' => [['4.8', '4.7', '4.6', '4.5', '4.4', '4.3', '4.2', '4.1'], ['3.1', '3.2', '3.3', '3.4', '3.5', '3.6', '3.7', '3.8']],
    ];

    public function mount(Evaluacion $evaluacion): void
    {
        $this->evaluacion = $evaluacion->load('detalles');
    }

    public function seleccionar(string $pieza): void
    {
        // Solo piezas FDI válidas (el método es invocable desde el navegador)
        if (! in_array($pieza, collect(self::ARCADAS)->flatten()->all(), true)) {
            return;
        }

        $this->piezaSeleccionada = $pieza;

        $detalle = $this->evaluacion->detalles->firstWhere('pieza', $pieza);

        $mapa = self::mapaCondiciones($detalle?->condiciones);

        $this->condiciones = array_keys($mapa);
        $this->tratadas = array_keys(array_filter($mapa));
        $this->diagnostico = $detalle->diagnostico ?? '';
        $this->hecho = (bool) ($detalle->hecho ?? false);
    }

    /**
     * Normaliza condiciones a mapa clave => tratada(bool).
     * Acepta el formato lista viejo (["caries"]) por compatibilidad.
     */
    public static function mapaCondiciones(?array $condiciones): array
    {
        if (blank($condiciones)) {
            return [];
        }

        if (array_is_list($condiciones)) {
            return array_fill_keys($condiciones, false);
        }

        return array_map(fn ($v) => (bool) $v, $condiciones);
    }

    public function guardar(): void
    {
        if (! $this->piezaSeleccionada) {
            return;
        }

        if (! auth()->user()?->can('update', $this->evaluacion)) {
            Notification::make()
                ->title('Sin permiso para editar el odontograma')
                ->danger()
                ->send();

            return;
        }

        $diagnostico = trim($this->diagnostico);

        // Mapa condición => tratada, solo claves del catálogo
        $mapa = collect($this->condiciones)
            ->filter(fn ($c) => array_key_exists($c, \App\Models\EvaluacionDetalle::CONDICIONES))
            ->unique()
            ->mapWithKeys(fn ($c) => [$c => in_array($c, $this->tratadas, true)])
            ->all();

        if ($mapa === [] && $diagnostico === '') {
            // Sin condiciones ni nota = pieza sana: se elimina el registro
            $this->evaluacion->detalles()
                ->where('pieza', $this->piezaSeleccionada)
                ->delete();
        } else {
            // 'hecho' (pieza completa) se deriva: todas las condiciones tratadas.
            // Si solo hay nota libre, manda el checkbox global.
            $hechoPieza = $mapa !== []
                ? ! in_array(false, $mapa, true)
                : $this->hecho;

            $this->evaluacion->detalles()->updateOrCreate(
                ['pieza' => $this->piezaSeleccionada],
                ['condiciones' => $mapa ?: null, 'diagnostico' => $diagnostico ?: null, 'hecho' => $hechoPieza],
            );
        }

        $this->evaluacion->refresh()->load('detalles');

        Notification::make()
            ->title("Pieza {$this->piezaSeleccionada} actualizada")
            ->success()
            ->send();
    }

    /** Estado visual de una pieza: vacio | pendiente | hecho. */
    public function estadoDe(string $pieza): string
    {
        $detalle = $this->evaluacion->detalles->firstWhere('pieza', $pieza);

        if (! $detalle || (blank($detalle->diagnostico) && blank($detalle->condiciones))) {
            return 'vacio';
        }

        return $detalle->hecho ? 'hecho' : 'pendiente';
    }

    public function diagnosticoDe(string $pieza): ?string
    {
        return $this->evaluacion->detalles->firstWhere('pieza', $pieza)?->diagnostico;
    }

    /** @return array<string> Claves de las condiciones de la pieza. */
    public function condicionesDe(string $pieza): array
    {
        return array_keys(self::mapaCondiciones(
            $this->evaluacion->detalles->firstWhere('pieza', $pieza)?->condiciones
        ));
    }

    /** @return array<string> Condiciones de la pieza ya tratadas. */
    public function tratadasDe(string $pieza): array
    {
        return array_keys(array_filter(self::mapaCondiciones(
            $this->evaluacion->detalles->firstWhere('pieza', $pieza)?->condiciones
        )));
    }

    public function tieneCondicion(string $pieza, string $condicion): bool
    {
        return in_array($condicion, $this->condicionesDe($pieza), true);
    }

    /**
     * Color principal de la corona: la primera condición con color
     * (ausente no pinta). Las demás se muestran como puntos.
     */
    public function colorDe(string $pieza): ?string
    {
        foreach ($this->condicionesDe($pieza) as $condicion) {
            $color = \App\Models\EvaluacionDetalle::CONDICIONES[$condicion]['color'] ?? null;

            if ($color !== null) {
                return $color;
            }
        }

        $detalle = $this->evaluacion->detalles->firstWhere('pieza', $pieza);

        return blank($detalle?->diagnostico) ? null : '#f59e0b'; // solo nota libre
    }

    /** Colores secundarios (condiciones adicionales) para los puntos bajo el diente. */
    public function coloresExtraDe(string $pieza): array
    {
        $colores = collect($this->condicionesDe($pieza))
            ->map(fn ($c) => \App\Models\EvaluacionDetalle::CONDICIONES[$c]['color'] ?? null)
            ->filter()
            ->values();

        return $colores->slice(1, 3)->all();
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

    public function render()
    {
        return view('livewire.odontograma');
    }
}
