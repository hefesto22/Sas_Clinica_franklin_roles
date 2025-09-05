<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Filament\Resources\EventResource;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use App\Models\CambioEvento;
use Illuminate\Support\Facades\Auth;
use App\Helpers\HorarioHelper;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Actions\CreateAction;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Livewire\Attributes\Url;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\ClienteNota;
use App\Models\ClienteActividad;
//aca ya sirve refrescando manua 
class CalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = Event::class;


    #[Url(as: 'consultorio')]     // se refleja como ?consultorio=ID en la URL
    public ?int $consultorioFilter = 1;

    protected $casts = [
        'consultorioFilter' => 'integer',
    ];

    protected function headerActions(): array
    {
        return [
            // Crear evento
            \Saade\FilamentFullCalendar\Actions\CreateAction::make()
                ->label('Crear evento')
                ->icon('heroicon-o-plus')
                ->form(fn() => $this->getCreateFormSchema())
                ->mountUsing(function (Forms\Form $form, array $arguments) {
                    $form->fill([
                        'start_date' => isset($arguments['start'])
                            ? Carbon::parse($arguments['start'])->format('Y-m-d')
                            : now()->format('Y-m-d'),
                        'start_time' => isset($arguments['start'])
                            ? Carbon::parse($arguments['start'])->format('H:i')
                            : null,
                        'end_time'   => isset($arguments['end'])
                            ? Carbon::parse($arguments['end'])->format('H:i')
                            : null,
                    ]);
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $start = Carbon::parse("{$data['start_date']} {$data['start_time']}:00");
                    $end   = isset($data['end_time'])
                        ? Carbon::parse("{$data['start_date']} {$data['end_time']}:00")
                        : (clone $start)->addMinutes(30);

                    $data['start_at'] = $start;
                    $data['end_at']   = $end;

                    unset($data['start_date'], $data['start_time'], $data['end_time']);

                    $data['estado']     = $data['estado'] ?? 'Pendiente';
                    $data['created_by'] = Auth::id();

                    return $data;
                })
                ->using(function (array $data) {
                    $especialidades = $data['especialidades'] ?? [];
                    $servicios      = $data['servicios'] ?? [];
                    $canceladoId    = $data['cancelado_evento_id'] ?? null;

                    unset($data['especialidades'], $data['servicios'], $data['cancelado_evento_id']);

                    $event = Event::create($data);

                    if ($especialidades) {
                        $event->especialidades()->sync($especialidades);
                    }
                    if ($servicios) {
                        $event->servicios()->sync($servicios);
                    }

                    // 🔹 si era una cancelada, eliminarla
                    if ($canceladoId) {
                        Event::where('id', $canceladoId)
                            ->where('estado', 'Cancelado')
                            ->delete();
                    }

                    return $event;
                }),


            // Filtro por consultorio
            Action::make('filtrarConsultorio')
                ->label('Filtrar por consultorio')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('consultorio_id')
                        ->label('Consultorio')
                        ->searchable()
                        ->preload()
                        ->options(\App\Models\Consultorio::pluck('nombre', 'id'))
                        ->native(false)
                        ->placeholder('Todos'),
                ])
                ->modalHeading('Filtrar por consultorio')
                ->modalSubmitActionLabel('Aplicar')
                ->action(function (array $data): void {
                    $this->consultorioFilter = isset($data['consultorio_id'])
                        ? (int) $data['consultorio_id']
                        : null;
                }),

            // Quitar filtro
            Action::make('limpiarFiltroConsultorio')
                ->label('Quitar filtro')
                ->icon('heroicon-o-x-mark')
                ->color('secondary')
                ->visible(fn() => filled($this->consultorioFilter))
                ->action(function (): void {
                    $this->consultorioFilter = null;
                }),
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return Event::with(['cliente:id,nombre', 'consultorio:id,nombre', 'doctor:id,name']) // 👈 agrega la relación doctor
            ->whereBetween('start_at', [$fetchInfo['start'], $fetchInfo['end']])
            ->where('estado', '!=', 'Cancelado')   // ⬅️ excluye canceladas
            ->when(!is_null($this->consultorioFilter), function ($q) {
                $q->where('consultorio_id', (int) $this->consultorioFilter);
            })
            ->get()
            ->map(fn(Event $event) => [
                'id'    => $event->id,
                'title' => ($event->cliente->nombre ?? 'Sin nombre')
                    . ' - ' . ($event->consultorio->nombre ?? 'Sin consultorio')
                    . ' - ' . ($event->doctor->name ?? 'Sin doctor'), // 👈 aquí agregamos el doctor
                'start' => $event->start_at instanceof \Carbon\Carbon ? $event->start_at->toIso8601String() : $event->start_at,
                'end'   => $event->end_at   instanceof \Carbon\Carbon ? $event->end_at->toIso8601String()   : $event->end_at,
                'color' => match ($event->estado) {
                    'Confirmado'  => 'green',
                    'Reagendado'  => 'blue',
                    'Reagendando' => 'orange',
                    'Se Presentó' => 'teal',
                    default       => 'gray',
                },
            ])
            ->all();
    }



    protected function getHeading(): string
    {
        if ($this->consultorioFilter) {
            $nombre = \App\Models\Consultorio::find($this->consultorioFilter)?->nombre ?? 'Consultorio';
            return "Agenda — {$nombre}";
        }

        return 'Agenda';
    }



    public function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('cliente_id')
                ->label('Paciente')
                ->relationship('cliente', 'nombre')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Actions::make([
                \Filament\Forms\Components\Actions\Action::make('contactar')
                    ->label('Contactar por WhatsApp')
                    ->color('success')
                    ->icon('heroicon-o-phone')
                    ->url(function ($record) {
                        $telefono = preg_replace('/\D/', '', $record->telefono ?? $record->cliente?->telefono);
                        if (!filled($telefono)) {
                            return '#';
                        }

                        $nombre = $record->cliente?->nombre ?? '';
                        $fecha = optional(Carbon::parse($record?->start_at)->locale('es'))->translatedFormat('l d/m/Y h:i A');

                        $mensaje = "Hola " . ($nombre ? "*{$nombre}*" : "") . ", le saludamos desde la clínica.\n\n" .
                            "¿Podrá asistir a su cita programada el día {$fecha}?\n\n" .
                            "Por favor confirme su asistencia respondiendo:\n" .
                            "✅ Sí\n❌ No\n🔁 Reagendar\n\n" .
                            "Agradecemos su pronta respuesta.";

                        return "https://wa.me/504{$telefono}?text=" . urlencode($mensaje);
                    }, true)
                    ->openUrlInNewTab()
                    ->visible(fn($record) => filled($record?->telefono ?? $record?->cliente?->telefono)),
            ])->columnSpanFull()->hiddenLabel(),

            Forms\Components\Select::make('consultorio_id')
                ->label('Consultorio')
                ->relationship('consultorio', 'nombre')
                ->required()
                ->searchable()
                ->preload()
                ->reactive(), // <--- ESTE ES CLAVE

            Forms\Components\Select::make('especialidades')
                ->label('Especialidades')
                ->multiple()
                ->searchable()
                ->preload()
                ->relationship('especialidades', 'nombre')
                ->required()
                ->reactive(),

            Forms\Components\Select::make('servicios')
                ->label('Servicios')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn(callable $get) => \App\Models\ServicioEspecialidad::whereIn('especialidad_id', $get('especialidades') ?? [])
                    ->pluck('nombre', 'id'))
                ->required()
                ->reactive()
                ->disabled(fn(callable $get) => empty($get('especialidades')))
                ->afterStateHydrated(function ($component, $state, $record) {
                    $component->state(
                        $record?->servicios()->pluck('servicios.id')->toArray()
                    );
                }),

            Forms\Components\Actions::make([
                \Filament\Forms\Components\Actions\Action::make('confirmado')
                    ->label('Confirmar')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(
                        fn($record) =>
                        $record
                            && $record->estado !== 'Confirmado'
                            && $record->estado !== 'Reagendando'
                    )
                    ->action(function ($record) {
                        $record->estado = 'Confirmado';
                        $record->save();
                    }),

                \Filament\Forms\Components\Actions\Action::make('se_presento')
                    ->label('Se Presentó')
                    ->color('primary')
                    ->icon('heroicon-o-user')
                    ->visible(fn($record) => $record && $record->estado === 'Confirmado')
                    ->modalHeading('Registrar asistencia')
                    ->modalSubmitActionLabel('Guardar y cerrar')
                    ->form(function ($record) {
                        $servicio = $record->servicios->first();

                        // Opciones para checkboxes: id => "fecha — contenido..."
                        $opcionesNotas = ClienteNota::query()
                            ->where('cliente_id', $record->cliente_id)
                            ->where('leida', 0)
                            ->orderByDesc('created_at')
                            ->get(['id', 'contenido', 'created_at'])
                            ->mapWithKeys(function ($n) {
                                $label = $n->created_at->format('d/m/Y H:i') . ' — ' . Str::limit($n->contenido, 80);
                                return [$n->id => $label];
                            })
                            ->toArray();

                        return [
                            // 🔸 Selección manual de notas no leídas
                            Forms\Components\Section::make('Notas no leídas')
                                ->description('Selecciona las notas que quieres marcar como leídas')
                                ->schema([
                                    Forms\Components\CheckboxList::make('nota_ids_a_marcar')
                                        ->options($opcionesNotas)
                                        ->columns(1)
                                        ->bulkToggleable(), // permite seleccionar/deseleccionar todas
                                ])
                                ->visible(fn() => ! empty($opcionesNotas))
                                ->collapsed(false)
                                ->columnSpanFull(),

                            // Datos de la atención (editables)
                            Forms\Components\TextInput::make('actividad')
                                ->label('Servicio / Actividad')
                                ->default($servicio?->nombre ?? 'Servicio no especificado')
                                ->required(),

                            Forms\Components\TextInput::make('pago')
                                ->label('Pago')
                                ->numeric()
                                ->prefix('L')
                                ->default($servicio?->precio ?? 0)
                                ->rule('numeric')
                                ->rule('min:0'),

                            Forms\Components\Textarea::make('nota')
                                ->label('Nueva nota (opcional)')
                                ->rows(3)
                                ->maxLength(500),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            // 1) Registrar actividad
                            ClienteActividad::create([
                                'cliente_id' => $record->cliente_id,
                                'fecha'      => now(),
                                'actividad'  => $data['actividad'],
                                'pago'       => $data['pago'] ?? 0,
                            ]);

                            // 2) Guardar nueva nota (opcional)
                            if (!empty($data['nota'])) {
                                ClienteNota::create([
                                    'cliente_id' => $record->cliente_id,
                                    'contenido'  => $data['nota'],
                                    'leida'      => 0,
                                    'created_by' => Auth::id(),
                                ]);
                            }

                            // 3) Marcar como leídas SOLO las seleccionadas
                            if (!empty($data['nota_ids_a_marcar'])) {
                                ClienteNota::whereIn('id', $data['nota_ids_a_marcar'])
                                    ->update(['leida' => 1, 'updated_at' => now()]);
                            }

                            // 4) Eliminar la cita (ya atendida)
                            $record->delete();
                        });

                        Notification::make()
                            ->title('Asistencia registrada')
                            ->body('Se registró la actividad, se procesaron las notas seleccionadas y se eliminó la cita.')
                            ->success()
                            ->send();

                        $this->dispatch('refreshCalendar');
                    }),



                //aca iniciamos no se presento

                \Filament\Forms\Components\Actions\Action::make('no_se_presento')
                    ->label('No se presentó')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn($record) => $record?->estado === 'Confirmado')
                    ->action(function ($record) {
                        $horaDeseada   = \Carbon\Carbon::parse($record->start_at)->format('H:i:s');
                        $consultorioId = $record->consultorio_id;

                        // Empezar desde el día siguiente
                        $proximaFecha = \Carbon\Carbon::parse($record->start_at)->copy()->addDay();

                        // Si cae domingo, mover a lunes
                        if ($proximaFecha->isSunday()) {
                            $proximaFecha->addDay();
                        }

                        $fechaDisponible = null;

                        while (!$fechaDisponible) {
                            // Evita domingos dentro del bucle también
                            if ($proximaFecha->isSunday()) {
                                $proximaFecha->addDay();
                                continue;
                            }

                            $fechaHora = $proximaFecha->copy()->setTimeFromTimeString($horaDeseada);

                            $yaOcupado = \App\Models\Event::where('consultorio_id', $consultorioId)
                                ->where('start_at', $fechaHora)
                                ->exists();

                            if (!$yaOcupado) {
                                $fechaDisponible = $fechaHora;
                                break;
                            }

                            $proximaFecha->addDay();
                        }

                        // Crear nuevo evento base
                        $nuevo = \App\Models\Event::create([
                            'cliente_id'     => $record->cliente_id,
                            'consultorio_id' => $record->consultorio_id,
                            'usuario_id'     => $record->usuario_id,
                            'start_at'       => $fechaDisponible,
                            'end_at'         => $fechaDisponible->copy()->addMinutes(30), // cambia a 30 si tu slot es de 30'
                            'estado'         => 'Pendiente',
                            'created_by'     => \Illuminate\Support\Facades\Auth::id(),
                        ]);

                        // Sincronizar especialidades y servicios
                        $nuevo->especialidades()->sync($record->especialidades->pluck('id')->toArray());
                        $nuevo->servicios()->sync($record->servicios->pluck('id')->toArray());


                        // Eliminar el evento original
                        $record->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('Reagendado')
                            ->body('El paciente fue reagendado para el ' . $fechaDisponible->locale('es')->translatedFormat('l d \\d\\e F h:i A'))
                            ->success()
                            ->send();
                    }),

                //aca iniciamos reagendacion directa
                \Filament\Forms\Components\Actions\Action::make('reagendar_directo')
                    ->label('Reagendar (Directo)')
                    ->color('warning')
                    ->icon('heroicon-o-calendar')
                    ->visible(fn($record) => $record && in_array($record->estado, ['Pendiente', 'Reagendado']))
                    ->action(function ($record) {
                        $horaDeseada   = \Carbon\Carbon::parse($record->start_at)->format('H:i:s');
                        $consultorioId = $record->consultorio_id;

                        // Comienza desde el día siguiente
                        $proximaFecha = \Carbon\Carbon::parse($record->start_at)->copy()->addDay();

                        // Si cae en domingo, pasa a lunes
                        if ($proximaFecha->isSunday()) {
                            $proximaFecha->addDay();
                        }

                        $fechaDisponible = null;

                        while (!$fechaDisponible) {
                            // Si en el ciclo volvemos a caer en domingo, saltar a lunes
                            if ($proximaFecha->isSunday()) {
                                $proximaFecha->addDay();
                                continue;
                            }

                            $fechaHora = $proximaFecha->copy()->setTimeFromTimeString($horaDeseada);

                            $yaOcupado = \App\Models\Event::where('consultorio_id', $consultorioId)
                                ->where('start_at', $fechaHora)
                                ->exists();

                            if (!$yaOcupado) {
                                $fechaDisponible = $fechaHora;
                                break;
                            }

                            $proximaFecha->addDay();
                        }

                        // Actualizar el evento actual (ajusta la duración si usas 30 min)
                        $record->start_at = $fechaDisponible;
                        $record->end_at   = $fechaDisponible->copy()->addMinutes(20);
                        $record->estado   = 'Reagendado';
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Cita reagendada')
                            ->body('La cita ha sido reagendada automáticamente a la próxima fecha libre (evitando domingos).')
                            ->success()
                            ->send();
                    }),


                \Filament\Forms\Components\Actions\Action::make('reagendar')
                    ->label('Intercambiar')
                    ->color('warning')
                    ->icon('heroicon-o-calendar')
                    ->form([
                        Forms\Components\Select::make('evento_reemplazo_id')
                            ->label('Paciente alternativo')
                            ->searchable()
                            ->required()
                            ->options(function ($record) {
                                $inicio = \Carbon\Carbon::parse($record->start_at)->addDay()->startOfDay();
                                $fin    = \Carbon\Carbon::parse($record->start_at)->addDays(5)->endOfDay();

                                // Ventana de la MISMA HORA (00–59 min)
                                $h      = \Carbon\Carbon::parse($record->start_at);
                                $horaIni = $h->copy()->startOfHour()->format('H:i:s'); // ej. 08:00:00
                                $horaFin = $h->copy()->endOfHour()->format('H:i:s');   // ej. 08:59:59

                                return \App\Models\Event::query()
                                    ->where('id', '!=', $record->id)
                                    ->where('estado', 'Pendiente')
                                    ->whereBetween('start_at', [$inicio, $fin])           // próximos 5 días
                                    ->whereTime('start_at', '>=', $horaIni)               // misma hora
                                    ->whereTime('start_at', '<=', $horaFin)
                                    // ->where('consultorio_id', $record->consultorio_id)  // (opcional) mismo consultorio
                                    ->orderBy('start_at')
                                    ->get()
                                    ->mapWithKeys(fn($e) => [
                                        $e->id => $e->cliente->nombre . ' (' .
                                            \Carbon\Carbon::parse($e->start_at)
                                            ->locale('es')->translatedFormat('l d/m h:i A') . ')',
                                    ]);
                            }),
                    ])
                    ->action(function ($data, $record) {
                        $eventoA = $record;
                        $eventoB = \App\Models\Event::find($data['evento_reemplazo_id']);

                        if (!$eventoB) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('No se encontró el evento alternativo.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Cambiar ambos eventos a "Reagendando"
                        $eventoA->update(['estado' => 'Reagendando']);
                        $eventoB->update(['estado' => 'Reagendando']);

                        // Registrar solicitud
                        \App\Models\CambioEvento::create([
                            'evento_id_origen' => $eventoA->id,
                            'evento_id_destino' => $eventoB->id,
                            'created_by' => \Illuminate\Support\Facades\Auth::id(),
                            'estado' => 'pendiente',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Solicitud enviada')
                            ->body('Se ha solicitado el cambio. Ambos pacientes están en estado "Reagendando".')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record?->estado === 'Pendiente'),


                \Filament\Forms\Components\Actions\Action::make('asignar_cancelado_a_este_horario')
                    ->label('Asignar cancelado a este horario')
                    ->color('secondary')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn($record) => $record && in_array($record->estado, ['Pendiente', 'Confirmado', 'Reagendado', 'Reagendando']))
                    ->form([
                        Forms\Components\Select::make('cancelado_id')
                            ->label('Selecciona una cita cancelada')
                            ->searchable()
                            ->required()
                            ->options(function ($record) {
                                return Event::with('cliente:id,nombre')
                                    ->where('estado', 'Cancelado')
                                    ->orderBy('start_at', 'asc') // de más antigua a más reciente
                                    ->limit(200)
                                    ->get()
                                    ->mapWithKeys(fn($e) => [
                                        $e->id => ($e->cliente->nombre ?? 'Sin nombre') . ' — ' .
                                            Carbon::parse($e->start_at)->locale('es')->translatedFormat('ddd D/M h:mm A'),
                                    ])
                                    ->toArray();
                            }),
                    ])
                    ->action(function (array $data, $record) {
                        DB::transaction(function () use ($data, $record) {
                            /** @var Event $eventoActual */
                            $eventoActual = $record;

                            /** @var Event|null $eventoCancelado */
                            $eventoCancelado = Event::with('cliente')->find($data['cancelado_id']);
                            if (! $eventoCancelado || $eventoCancelado->estado !== 'Cancelado') {
                                Notification::make()
                                    ->title('No disponible')
                                    ->body('La cita seleccionada ya no está en estado Cancelado.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Guardar slot original del evento actual
                            $slotStart        = Carbon::parse($eventoActual->start_at);
                            $slotEnd          = Carbon::parse($eventoActual->end_at);
                            $duracionMin      = $slotEnd->diffInMinutes($slotStart);
                            $consultorioId    = $eventoActual->consultorio_id;

                            // 1) Buscar próxima fecha disponible para el evento actual (misma hora, sin domingos)
                            $horaDeseada  = $slotStart->format('H:i:s');
                            $proximaFecha = $slotStart->copy()->addDay();
                            if ($proximaFecha->isSunday()) {
                                $proximaFecha->addDay();
                            }

                            $estadosOcupados = ['Pendiente', 'Confirmado', 'Reagendado', 'Reagendando']; // Cancelado no bloquea

                            $fechaDisponible = null;
                            while (! $fechaDisponible) {
                                if ($proximaFecha->isSunday()) { // evitar domingos
                                    $proximaFecha->addDay();
                                    continue;
                                }

                                $fechaHora = $proximaFecha->copy()->setTimeFromTimeString($horaDeseada);

                                $yaOcupado = Event::where('consultorio_id', $consultorioId)
                                    ->whereIn('estado', $estadosOcupados)
                                    ->where('start_at', $fechaHora)
                                    ->where('id', '!=', $eventoActual->id)
                                    ->exists();

                                if (! $yaOcupado) {
                                    $fechaDisponible = $fechaHora;
                                    break;
                                }

                                $proximaFecha->addDay();
                            }

                            // 2) Mover el evento actual a la próxima fecha disponible
                            $eventoActual->start_at = $fechaDisponible;
                            $eventoActual->end_at   = $fechaDisponible->copy()->addMinutes($duracionMin);
                            $eventoActual->estado   = 'Reagendado';
                            $eventoActual->save();

                            // 3) El evento "Cancelado" toma el slot original del evento actual
                            $eventoCancelado->consultorio_id = $consultorioId;
                            $eventoCancelado->start_at       = $slotStart;
                            $eventoCancelado->end_at         = $slotEnd;
                            $eventoCancelado->estado         = 'Pendiente'; // o 'Confirmado' si prefieres
                            $eventoCancelado->save();
                        });

                        Notification::make()
                            ->title('Reasignación completa')
                            ->body('Se movió la cita actual a la próxima fecha disponible y la cita cancelada tomó su horario.')
                            ->success()
                            ->send();

                        $this->dispatch('refreshCalendar');
                    }),

                \Filament\Forms\Components\Actions\Action::make('cancelar_cita')   // ⬅️ NUEVO
                    ->label('Cita cancelada')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation() // opcional
                    ->visible(
                        fn($record) =>
                        $record && in_array($record->estado, ['Pendiente', 'Confirmado', 'Reagendado', 'Reagendando'])
                    )
                    ->action(function ($record) {
                        $record->update(['estado' => 'Cancelado']);

                        \Filament\Notifications\Notification::make()
                            ->title('Cita cancelada')
                            ->body('La cita fue marcada como Cancelada.')
                            ->warning()
                            ->send();

                        $this->dispatch('refreshCalendar'); // si usas el widget/calendario
                    }),

            ])

                ->columnSpanFull()
                ->hiddenLabel(),


        ];
    }
    protected function getFullCalendarOptions(): array
    {
        return [
            'selectable' => true,
        ];
    }
    protected function getNavigateToCreateEventUrl(string $date): ?string
    {
        return route('filament.admin.resources.events.create', [
            'start_date' => $date,
        ]);
    }

    // aca comienza la maravila
    protected function getCreateFormSchema(): array
    {
        return [
            Forms\Components\Grid::make([
                'default' => 1,   // 1 columna en pantallas pequeñas
                'md'      => 2,   // 2 columnas desde md en adelante
            ])->schema([

                Forms\Components\Select::make('cancelado_evento_id')
                    ->label('Citas canceladas')
                    ->searchable()
                    ->preload()
                    ->reactive() // <-- importante para recalcular el helperText al seleccionar
                    ->options(function () {
                        return \App\Models\Event::query()
                            ->with([
                                'cliente:id,nombre',
                                'consultorio:id,nombre',
                                'doctor:id,name',
                            ])
                            ->where('estado', 'Cancelado')
                            ->orderByDesc('start_at')
                            ->limit(200)
                            ->get()
                            ->mapWithKeys(function ($e) {
                                $nombreCliente = $e->cliente->nombre ?? 'Sin nombre';
                                $fechaStr = \Illuminate\Support\Carbon::parse($e->start_at)
                                    ->locale('es')
                                    ->isoFormat('ddd D/MM, h:mm a'); // ej. "vie 12/09, 8:30 a. m."

                                $consultorio = $e->consultorio->nombre ?? 'Sin consultorio';
                                $doctor      = $e->doctor?->name ? ('Doctor: ' . $e->doctor->name) : null;

                                $partes = array_filter([$nombreCliente, $fechaStr, $consultorio, $doctor]);
                                return [$e->id => implode(' — ', $partes)];
                            })
                            ->toArray();
                    }),

                // Botones: elegir una sugerencia y autocompletar fecha/hora (y consultorio)
                Forms\Components\ToggleButtons::make('sugerencia_btn')
                    ->label('Elegir sugerencia')
                    ->helperText('Pulsa un botón para colocar automáticamente la fecha y la hora.')
                    ->inline()
                    ->multiple(false)
                    ->reactive()
                    ->options(function (\Filament\Forms\Get $get) {
                        $evId = $get('cancelado_evento_id');
                        if (!$evId) return [];

                        /** @var \App\Models\Event|null $ev */
                        $ev = \App\Models\Event::find($evId);
                        if (!$ev) return [];

                        // ✅ Consultorio a usar: primero el del formulario, si no, el de la cancelada
                        $consultorioId = (int) ($get('consultorio_id') ?: $ev->consultorio_id);
                        if (!$consultorioId) return [];

                        // Hora preferida (HH:mm) tomada de la cancelada
                        $horaPref = \Illuminate\Support\Carbon::parse($ev->start_at)->format('H:i');

                        $sugs    = [];
                        $dia     = now()->startOfDay()->copy();
                        $maxDias = 60;  // busca hasta 60 días hacia adelante para completar 5 sugerencias
                        $objetivo = 5;  // 5 sugerencias EXACTAS de 5 días distintos

                        for ($i = 0; $i < $maxDias && count($sugs) < $objetivo; $i++, $dia->addDay()) {
                            // omitir domingos
                            if ($dia->isSunday()) continue;

                            $fecha = $dia->format('Y-m-d');

                            // Opciones disponibles del consultorio para ese día
                            // Debe devolver ['HH:mm' => '08:00 a. m.', ...] solo para $consultorioId
                            $opc = \App\Helpers\HorarioHelper::opcionesDisponibles($consultorioId, $fecha);
                            if (empty($opc)) continue;

                            // ✅ Solo sugerimos si la misma hora (horaPref) está libre ese día
                            if (array_key_exists($horaPref, $opc)) {
                                $value = $fecha . ' ' . $horaPref; // "YYYY-MM-DD HH:MM"
                                $label = $dia->locale('es')->isoFormat('ddd D/MM') . ' — ' . $opc[$horaPref];

                                $sugs[$value] = $label;
                            }
                        }

                        return $sugs; // Ej: ['2025-09-05 08:00' => 'vie 5/09 — 8:00 a. m.', ...]
                    })


                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                        if (! $state) return;

                        try {
                            // Valor esperado: "YYYY-MM-DD HH:MM"
                            [$d, $t] = explode(' ', trim($state), 2);

                            // 1) Fecha y hora de inicio
                            $set('start_date', $d);
                            $set('start_time', $t);

                            // 2) Hora de finalización (+30 min)
                            try {
                                $h = \Illuminate\Support\Carbon::createFromFormat('H:i', $t);
                                $set('end_time', $h->copy()->addMinutes(30)->format('H:i'));
                            } catch (\Throwable $e) {
                                // no-op
                            }

                            // 3) Datos del evento cancelado (cliente, teléfono, especialidades, servicios, doctor)
                            $evId = $get('cancelado_evento_id');
                            if ($evId) {
                                /** @var \App\Models\Event|null $ev */
                                $ev = \App\Models\Event::with([
                                    'cliente:id,telefono',
                                    'especialidades:id',
                                    'servicios:id',
                                ])->find($evId);

                                if ($ev) {
                                    // (opcional) fijar consultorio SOLO si aún no hay uno seleccionado
                                    $consultorioActual = $get('consultorio_id');
                                    if (empty($consultorioActual) && $ev->consultorio_id) {
                                        $set('consultorio_id', (int) $ev->consultorio_id);
                                    }

                                    // ✅ Doctor de la cancelada (no sobrescribe si ya hay uno seleccionado)
                                    $doctorActual = $get('doctor_id');
                                    if (empty($doctorActual) && $ev->doctor_id) {
                                        $set('doctor_id', (int) $ev->doctor_id);
                                    }

                                    // Flag para no limpiar servicios al setear especialidades
                                    $set('autofill_cancelado', '1');

                                    // Cliente y teléfono (quedan EDITABLES)
                                    $set('cliente_id', $ev->cliente_id);
                                    if ($ev->cliente?->telefono) {
                                        $set('telefono', $ev->cliente->telefono);
                                    }

                                    // Especialidades y servicios desde la cancelada (EDITABLES)
                                    $set('especialidades', $ev->especialidades->pluck('id')->toArray());
                                    $set('servicios', $ev->servicios->pluck('id')->toArray());

                                    // Quitar flag
                                    $set('autofill_cancelado', null);
                                }
                            }
                        } catch (\Throwable $e) {
                            // no-op
                        }
                    })


                    ->hidden(fn(\Filament\Forms\Get $get) => ! $get('cancelado_evento_id')), // solo visible si hay cancelada
                Forms\Components\TextInput::make('autofill_cancelado')
                    ->hidden()
                    ->dehydrated(false),

                // Fila 1
                Forms\Components\Select::make('cliente_id')
                    ->label('Cliente')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(
                        fn(string $search) =>
                        \App\Models\Cliente::query()
                            ->where('nombre', 'like', "%{$search}%")
                            ->orWhere('dni', 'like', "%{$search}%")
                            ->limit(5)
                            ->pluck('nombre', 'id')
                    )
                    ->getOptionLabelUsing(fn($value) => \App\Models\Cliente::find($value)?->nombre)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state && ($cli = \App\Models\Cliente::find($state))) {
                            $set('telefono', $cli->telefono);
                        }
                    })
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if (! $value) return;

                            // si estás editando, excluye tu propio evento
                            $currentEventId = request()->route('record');

                            // estados que cuentan como cita "ocupada"
                            $estados = ['Pendiente', 'Confirmado', 'Reagendado', 'Reagendando'];

                            $inicio = now()->startOfDay();
                            $fin    = now()->addDays(25)->endOfDay();

                            $existe = \App\Models\Event::query()
                                ->where('cliente_id', $value)
                                ->whereIn('estado', $estados)
                                ->whereBetween('start_at', [$inicio, $fin])
                                ->when($currentEventId, fn($q) => $q->where('id', '!=', $currentEventId))
                                ->orderBy('start_at')
                                ->first();

                            if ($existe) {
                                $fecha = \Illuminate\Support\Carbon::parse($existe->start_at)->format('d/m/Y h:i A');
                                $fail("Este cliente ya tiene una cita {$existe->estado} el {$fecha}. No puede agendar otra como minimo dentro de 25 días.");
                            }
                        };
                    }),


                Forms\Components\TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->disabled()
                    ->dehydrated(true),

                // Fila 2
                Forms\Components\Select::make('especialidades')
                    ->label('Especialidades')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(\App\Models\Especialidad::pluck('nombre', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn(Forms\Set $set) => $set('servicios', [])),

                Forms\Components\Select::make('servicios')
                    ->label('Servicios')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->disabled(fn(Forms\Get $get) => empty($get('especialidades')))
                    ->relationship(
                        name: 'servicios',
                        titleAttribute: 'nombre',
                        modifyQueryUsing: fn($query, Forms\Get $get) =>
                        $query->whereIn('especialidad_id', $get('especialidades') ?: [-1])
                    ),

                // Fila 3
                Forms\Components\Select::make('consultorio_id')
                    ->label('Consultorio')
                    ->options(\App\Models\Consultorio::pluck('nombre', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive(),

                Forms\Components\Select::make('doctor_id')
                    ->label('Doctor')
                    ->relationship(
                        name: 'doctor',                      // relación en Event: doctor()
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query->role('doctor') // solo usuarios con rol doctor
                    )
                    ->searchable()
                    ->preload()
                    ->nullable() // ✅ el doctor es opcional (doctor_id NULL si no se selecciona)
                    ->default(function () {
                        $u = Auth::user();
                        return ($u instanceof \App\Models\User && $u->hasRole('doctor'))
                            ? $u->id
                            : null;
                    })
                    ->disabled(function () {
                        // Si el usuario autenticado es doctor, se autoselecciona y no puede cambiarlo
                        $u = Auth::user();
                        return $u instanceof \App\Models\User && $u->hasRole('doctor');
                    }),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Fecha')
                    ->required()
                    ->reactive()
                    ->native(false)
                    ->minDate(\Illuminate\Support\Carbon::today())
                    ->rule(function (\Filament\Forms\Get $get) {
                        return function (string $attribute, $value, $fail) use ($get) {
                            $fecha = \Illuminate\Support\Carbon::parse($value);

                            // No domingos
                            if ($fecha->isSunday()) {
                                $fail('Los domingos no se trabaja. Por favor seleccione otro día.');
                                return;
                            }

                            // Si hay una cancelada seleccionada: exigir fecha estrictamente posterior
                            $evId = $get('cancelado_evento_id');
                            if ($evId) {
                                $ev = \App\Models\Event::find($evId);
                                if ($ev && $ev->start_at) {
                                    $fechaCancelada = \Illuminate\Support\Carbon::parse($ev->start_at)->toDateString();
                                    if ($fecha->toDateString() <= $fechaCancelada) {
                                        $fail('La fecha debe ser estrictamente posterior a la fecha de la cita cancelada.');
                                    }
                                }
                            }
                        };
                    }),

                // Fila 4
                Forms\Components\Select::make('start_time')
                    ->label('Hora disponible')
                    ->required()
                    ->options(fn(Get $get) => HorarioHelper::opcionesDisponibles(
                        $get('consultorio_id'),
                        $get('start_date'),
                    ))
                    ->disabled(fn(Get $get) => ! $get('start_date') || ! $get('consultorio_id'))
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (! $state) {
                            $set('end_time', null);
                            return;
                        }

                        // Si usas únicamente start_time / end_time en el Widget:
                        $h = \Illuminate\Support\Carbon::createFromFormat('H:i', $state);
                        $set('end_time', $h->copy()->addMinutes(30)->format('H:i'));
                    }),

                Forms\Components\TimePicker::make('end_time')
                    ->label('Hora de finalización')
                    ->required()
                    ->disabled()
                    ->seconds(false)
                    ->step(20),
            ]),

        ];
    }
}
