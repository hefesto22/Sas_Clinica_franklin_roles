<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use App\Models\Cliente;
use Closure;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DatePicker;
use DateTimeInterface;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\EventResource\Pages\ListEvents;
use App\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Filament\Resources\EventResource\Pages\EditEvent;
use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Helpers\HorarioHelper;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\ServicioEspecialidad;


class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Eventos';
    protected static string | \UnitEnum | null $navigationGroup = 'Gestión de Calendario';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('cliente_id')
                ->label('Cliente')
                ->searchable()
                ->getSearchResultsUsing(fn(string $search) => Cliente::query()
                    ->where('nombre', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->limit(5)
                    ->pluck('nombre', 'id'))
                ->getOptionLabelUsing(fn($value): ?string => Cliente::find($value)?->nombre)
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state && ($c = Cliente::find($state))) {
                        $set('telefono', $c->telefono);
                    }
                })
                ->rule(function () {
                    return function (string $attribute, $value, Closure $fail) {

                        if (! $value) return;

                        // Si estás en /events/{record}/edit, este es el id del propio evento
                        $currentEventId = request()->route('record');

                        $proximo = Event::query()
                            ->where('cliente_id', $value)
                            ->where('estado', 'Pendiente')
                            ->whereBetween('start_at', [now(), now()->addDays(25)])
                            ->when($currentEventId, fn($q) => $q->where('id', '!=', $currentEventId))
                            ->orderBy('start_at', 'asc')
                            ->first();

                        if ($proximo) {
                            $fecha = Carbon::parse($proximo->start_at)->format('d/m/Y h:i A');
                            $fail("Este cliente ya tiene una cita pendiente el {$fecha}.");
                        }
                    };
                }),


            Select::make('consultorio_id')
                ->label('Consultorio')
                ->relationship('consultorio', 'nombre')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function (Get $get, Set $set) {
                    $set('start_date', null);
                    $set('start_time', null);
                    $set('start_at',   null);
                    $set('end_at',     null);
                }),



            Select::make('doctor_id')
                ->label('Doctor')
                ->relationship(
                    name: 'doctor',                      // relación en Event: doctor()
                    titleAttribute: 'name',
                    modifyQueryUsing: fn($query) => $query->role('doctor') // solo usuarios con rol doctor
                )
                ->searchable()
                ->preload()
                ->default(function () {
                    $u = Auth::user();
                    return ($u instanceof User && $u->hasRole('doctor'))
                        ? $u->id
                        : null;
                })
                ->nullable(), // 👈 ahora el campo puede quedar vacío

            // FECHA
            DatePicker::make('start_date')
                ->label('Fecha')
                ->native(false)
                ->minDate(now()->toDateString())
                ->required()
                ->reactive()
                ->dehydrated(false) // no va a BD
                ->disabled(fn(Get $get) => ! $get('consultorio_id'))
                ->afterStateHydrated(function (Set $set, ?Event $record) {
                    if ($record?->start_at) {
                        $date = $record->start_at instanceof DateTimeInterface
                            ? Carbon::parse($record->start_at)->toDateString()
                            : Carbon::parse((string) $record->start_at)->toDateString();

                        $set('start_date', $date);
                    }
                })
                ->afterStateUpdated(function (Set $set) {
                    $set('start_time', null);
                    $set('start_at',   null);
                    $set('end_at',     null);
                }),

            // HORA DISPONIBLE
            Select::make('start_time')
                ->label('Hora disponible')
                ->native(false)
                ->searchable(false)
                ->required()
                ->dehydrated(false) // no va a BD
                ->options(function (Get $get, ?Event $record) {
                    $opts = HorarioHelper::opcionesDisponibles(
                        $get('consultorio_id'),
                        $get('start_date'),
                    );

                    // Inyecta la hora actual del evento si no aparece en las opciones
                    if ($record?->start_at) {
                        $start = $record->start_at instanceof DateTimeInterface
                            ? Carbon::parse($record->start_at)
                            : Carbon::parse((string) $record->start_at);

                        $key   = $start->format('H:i');   // "08:00"
                        $label = $start->format('g:i A'); // "8:00 AM"

                        if (! isset($opts[$key])) {
                            $opts = [$key => $label . ' — actual'] + $opts;
                        }
                    }

                    return $opts;
                })
                ->disabled(fn(Get $get) => ! $get('consultorio_id') || ! $get('start_date'))
                ->afterStateHydrated(function (Set $set, ?Event $record) {
                    if ($record?->start_at) {
                        $start = $record->start_at instanceof DateTimeInterface
                            ? Carbon::parse($record->start_at)
                            : Carbon::parse((string) $record->start_at);

                        $set('start_time', $start->format('H:i'));                 // clave del select
                        $set('start_at',   $start->toDateTimeString());            // hidden
                    }

                    if ($record?->end_at) {
                        $end = $record->end_at instanceof DateTimeInterface
                            ? Carbon::parse($record->end_at)
                            : Carbon::parse((string) $record->end_at);

                        $set('end_at', $end->toDateTimeString());                 // hidden
                    }
                })
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if (! $state || ! $get('consultorio_id') || ! $get('start_date')) {
                        $set('start_at', null);
                        $set('end_at',   null);
                        return;
                    }

                    [$start, $end] = HorarioHelper::calcularRango(
                        (int) $get('consultorio_id'),
                        (string) $get('start_date'),
                        (string) $state
                    );

                    $set('start_at', $start->toDateTimeString());
                    $set('end_at',   $end->toDateTimeString());
                }),


            TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->disabled()
                ->maxLength(25),

            Select::make('estado')
                ->label('Estado')
                ->options([
                    'Pendiente'   => 'Pendiente',
                    'Reagendando' => 'Reagendando',
                    'Reagendado'  => 'Reagendado',
                    'Confirmado'  => 'Confirmado',
                    'Se Presentó' => 'Se Presentó',
                ])
                ->default('Pendiente')
                ->required()
                ->hiddenOn('create')   // Oculto en Create
                ->visibleOn('edit'),   // Visible en Edit


            Select::make('especialidades')
                ->label('Especialidades')
                ->relationship('especialidades', 'nombre')   // relación en tu modelo (belongsToMany)
                ->multiple()
                ->searchable()
                ->preload()
                ->reactive()
                // cuando cambie, limpiamos los servicios seleccionados
                ->afterStateUpdated(fn(Set $set) => $set('servicios', [])),

            // Servicios (muchos a muchos con tu modelo, pero cada Servicio pertenece a 1 Especialidad)
            Select::make('servicios')
                ->label('Servicios')
                ->relationship(
                    name: 'servicios',                       // relación belongsToMany en tu modelo (ej. Event::class)
                    titleAttribute: 'nombre',
                    modifyQueryUsing: function ($query, Get $get) {
                        // IDs de especialidades seleccionadas
                        $ids = $get('especialidades') ?: [-1];
                        // Filtra por la FK de Servicio hacia Especialidad
                        $query->whereIn('especialidad_id', $ids);
                    }
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->reactive()
                ->disabled(fn(Get $get) => empty($get('especialidades'))),

            // Estos sí van a BD
            Hidden::make('start_at')->required(),
            Hidden::make('end_at')->required(),
            Hidden::make('created_by')->default(fn() => Auth::id()),
            Hidden::make('updated_by')->default(fn() => Auth::id()),
        ])->columns(2);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('consultorio.nombre')
                    ->label('Consultorio')
                    ->sortable(),

                TextColumn::make('start_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

                TextColumn::make('estado')
                    ->badge()
                    ->colors([
                        'secondary' => 'Pendiente',
                        'warning'   => 'Reagendando',
                        'info'      => 'Reagendado',
                        'success'   => 'Confirmado',
                        'primary'   => 'Se Presentó',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->mutateDataUsing(function (array $data) {
                        $data['updated_by'] = Auth::id();
                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Puedes agregar RelationManagers si quieres manejar
            // especialidades/servicios desde el show/edit.
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit'   => EditEvent::route('/{record}/edit'),
        ];
    }
}
