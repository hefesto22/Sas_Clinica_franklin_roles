<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Helpers\HorarioHelper;
use Illuminate\Support\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\ServicioEspecialidad;

class EventResource extends \Filament\Resources\Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Eventos';
    protected static ?string $navigationGroup = 'Gestión de Calendario';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('cliente_id')
                ->label('Cliente')
                ->searchable()
                ->getSearchResultsUsing(fn(string $search) => \App\Models\Cliente::query()
                    ->where('nombre', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->limit(5)
                    ->pluck('nombre', 'id'))
                ->getOptionLabelUsing(fn($value): ?string => \App\Models\Cliente::find($value)?->nombre)
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state && ($c = \App\Models\Cliente::find($state))) {
                        $set('telefono', $c->telefono);
                    }
                })
                ->rule(function () {
                    return function (string $attribute, $value, \Closure $fail) {

                        if (! $value) return;

                        // Si estás en /events/{record}/edit, este es el id del propio evento
                        $currentEventId = request()->route('record');

                        $proximo = \App\Models\Event::query()
                            ->where('cliente_id', $value)
                            ->where('estado', 'Pendiente')
                            ->whereBetween('start_at', [now(), now()->addDays(25)])
                            ->when($currentEventId, fn($q) => $q->where('id', '!=', $currentEventId))
                            ->orderBy('start_at', 'asc')
                            ->first();

                        if ($proximo) {
                            $fecha = \Illuminate\Support\Carbon::parse($proximo->start_at)->format('d/m/Y h:i A');
                            $fail("Este cliente ya tiene una cita pendiente el {$fecha}.");
                        }
                    };
                }),


            Forms\Components\Select::make('consultorio_id')
                ->label('Consultorio')
                ->relationship('consultorio', 'nombre')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                    $set('start_date', null);
                    $set('start_time', null);
                    $set('start_at',   null);
                    $set('end_at',     null);
                }),

            // FECHA
            Forms\Components\DatePicker::make('start_date')
                ->label('Fecha')
                ->native(false)
                ->minDate(now()->toDateString())
                ->required()
                ->reactive()
                ->dehydrated(false) // no va a BD
                ->disabled(fn(Forms\Get $get) => ! $get('consultorio_id'))
                ->afterStateHydrated(function (Forms\Set $set, ?\App\Models\Event $record) {
                    if ($record?->start_at) {
                        $date = $record->start_at instanceof \DateTimeInterface
                            ? \Illuminate\Support\Carbon::parse($record->start_at)->toDateString()
                            : \Illuminate\Support\Carbon::parse((string) $record->start_at)->toDateString();

                        $set('start_date', $date);
                    }
                })
                ->afterStateUpdated(function (Forms\Set $set) {
                    $set('start_time', null);
                    $set('start_at',   null);
                    $set('end_at',     null);
                }),

            // HORA DISPONIBLE
            Forms\Components\Select::make('start_time')
                ->label('Hora disponible')
                ->native(false)
                ->searchable(false)
                ->required()
                ->dehydrated(false) // no va a BD
                ->options(function (Forms\Get $get, ?\App\Models\Event $record) {
                    $opts = \App\Helpers\HorarioHelper::opcionesDisponibles(
                        $get('consultorio_id'),
                        $get('start_date'),
                    );

                    // Inyecta la hora actual del evento si no aparece en las opciones
                    if ($record?->start_at) {
                        $start = $record->start_at instanceof \DateTimeInterface
                            ? \Illuminate\Support\Carbon::parse($record->start_at)
                            : \Illuminate\Support\Carbon::parse((string) $record->start_at);

                        $key   = $start->format('H:i');   // "08:00"
                        $label = $start->format('g:i A'); // "8:00 AM"

                        if (! isset($opts[$key])) {
                            $opts = [$key => $label . ' — actual'] + $opts;
                        }
                    }

                    return $opts;
                })
                ->disabled(fn(Forms\Get $get) => ! $get('consultorio_id') || ! $get('start_date'))
                ->afterStateHydrated(function (Forms\Set $set, ?\App\Models\Event $record) {
                    if ($record?->start_at) {
                        $start = $record->start_at instanceof \DateTimeInterface
                            ? \Illuminate\Support\Carbon::parse($record->start_at)
                            : \Illuminate\Support\Carbon::parse((string) $record->start_at);

                        $set('start_time', $start->format('H:i'));                 // clave del select
                        $set('start_at',   $start->toDateTimeString());            // hidden
                    }

                    if ($record?->end_at) {
                        $end = $record->end_at instanceof \DateTimeInterface
                            ? \Illuminate\Support\Carbon::parse($record->end_at)
                            : \Illuminate\Support\Carbon::parse((string) $record->end_at);

                        $set('end_at', $end->toDateTimeString());                 // hidden
                    }
                })
                ->reactive()
                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                    if (! $state || ! $get('consultorio_id') || ! $get('start_date')) {
                        $set('start_at', null);
                        $set('end_at',   null);
                        return;
                    }

                    [$start, $end] = \App\Helpers\HorarioHelper::calcularRango(
                        (int) $get('consultorio_id'),
                        (string) $get('start_date'),
                        (string) $state
                    );

                    $set('start_at', $start->toDateTimeString());
                    $set('end_at',   $end->toDateTimeString());
                }),


            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->disabled()
                ->maxLength(25),

            Forms\Components\Select::make('estado')
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


            Forms\Components\Select::make('especialidades')
                ->label('Especialidades')
                ->relationship('especialidades', 'nombre')   // relación en tu modelo (belongsToMany)
                ->multiple()
                ->searchable()
                ->preload()
                ->reactive()
                // cuando cambie, limpiamos los servicios seleccionados
                ->afterStateUpdated(fn(Set $set) => $set('servicios', [])),

            // Servicios (muchos a muchos con tu modelo, pero cada Servicio pertenece a 1 Especialidad)
            Forms\Components\Select::make('servicios')
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
            Forms\Components\Hidden::make('start_at')->required(),
            Forms\Components\Hidden::make('end_at')->required(),
            Forms\Components\Hidden::make('created_by')->default(fn() => Auth::id()),
            Forms\Components\Hidden::make('updated_by')->default(fn() => Auth::id()),
        ])->columns(2);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('consultorio.nombre')
                    ->label('Consultorio')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->colors([
                        'secondary' => 'Pendiente',
                        'warning'   => 'Reagendando',
                        'info'      => 'Reagendado',
                        'success'   => 'Confirmado',
                        'primary'   => 'Se Presentó',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['updated_by'] = Auth::id();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit'   => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
