<?php

namespace App\Filament\Resources\ConsultorioResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Helpers\HorarioHelper;


class TurnosRelationManager extends RelationManager
{
    protected static string $relationship = 'turnos';
    protected static ?string $title = 'Horarios y días de atención';

    // Crear/editar un turno individual
    public function form(Form $form): Form
    {
        $modoConsultorio = optional($this->getOwnerRecord())->modo_defecto ?? 'horario';

        return $form->schema([
            Forms\Components\Select::make('dia_semana')
                ->label('Día de la semana')
                ->options([
                    1 => 'Lunes',
                    2 => 'Martes',
                    3 => 'Miércoles',
                    4 => 'Jueves',
                    5 => 'Viernes',
                    6 => 'Sábado',
                    7 => 'Domingo',
                ])
                ->required(),

            // ⬇️ aquí van las horas enteras desde el helper
            Forms\Components\Select::make('hora_inicio')
                ->label('Hora inicio')
                ->options(HorarioHelper::horasEnteras()) // ['06:00' => '6:00 AM', ...]
                ->required()
                ->native(false),

            Forms\Components\Select::make('hora_fin')
                ->label('Hora fin')
                ->options(HorarioHelper::horasEnteras())
                ->required()
                ->native(false),

            Forms\Components\Hidden::make('modo')->default($modoConsultorio),

            Forms\Components\Placeholder::make('modo_info')
                ->label('Modo')
                ->content(fn($get) => $get('modo') === 'horario'
                    ? 'Horario (intervalos) — heredado del consultorio'
                    : 'Cupos por hora — heredado del consultorio'),

            Forms\Components\TextInput::make('slot_minutos')
                ->label('Duración del slot (min)')
                ->numeric()->minValue(5)->default(30)
                ->visible(fn($get) => ($get('modo') ?? $modoConsultorio) === 'horario'),

            Forms\Components\TextInput::make('cupos_por_hora')
                ->label('Cupos por hora')
                ->numeric()->minValue(1)->default(6)
                ->visible(fn($get) => ($get('modo') ?? $modoConsultorio) === 'cupos'),

            Forms\Components\Toggle::make('activo')->label('Activo')->default(true),
        ])->columns(2);
    }


    // Modal de creación múltiple (checkbox por día)
    protected function bulkSchema(): array
    {
        $modoConsultorio = optional($this->getOwnerRecord())->modo_defecto ?? 'horario';

        return [
            Forms\Components\CheckboxList::make('dias')
                ->label('Días de la semana')
                ->options([
                    1 => 'Lunes',
                    2 => 'Martes',
                    3 => 'Miércoles',
                    4 => 'Jueves',
                    5 => 'Viernes',
                    6 => 'Sábado',
                    7 => 'Domingo',
                ])
                ->columns(2)->required()
                ->helperText('Se creará un turno para cada día seleccionado.'),

            Forms\Components\Select::make('hora_inicio')
                ->label('Hora inicio')
                ->options(HorarioHelper::horasEnteras())
                ->required()
                ->native(false),

            Forms\Components\Select::make('hora_fin')
                ->label('Hora fin')
                ->options(HorarioHelper::horasEnteras())
                ->required()
                ->native(false),

            Forms\Components\Placeholder::make('modo_info')
                ->label('Modo')
                ->content($modoConsultorio === 'horario'
                    ? 'Horario (intervalos) — heredado del consultorio'
                    : 'Cupos por hora — heredado del consultorio'),

            Forms\Components\TextInput::make('slot_minutos')
                ->label('Duración del slot (min)')
                ->numeric()->minValue(5)->default(30)
                ->visible($modoConsultorio === 'horario'),

            Forms\Components\TextInput::make('cupos_por_hora')
                ->label('Cupos por hora')
                ->numeric()->minValue(1)->default(6)
                ->visible($modoConsultorio === 'cupos'),

            Forms\Components\Toggle::make('activo')->label('Activo')->default(true),
        ];
    }


    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dia_semana')
                    ->label('Día')->sortable()
                    ->formatStateUsing(fn($state) => [
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                        7 => 'Domingo',
                    ][$state] ?? $state),

                Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('Inicio')->sortable()
                    ->formatStateUsing(fn(?string $state) =>
                    $state !== null ? (HorarioHelper::horasEnteras()[$state] ?? $state) : null),

                Tables\Columns\TextColumn::make('hora_fin')
                    ->label('Fin')->sortable()
                    ->formatStateUsing(fn(?string $state) =>
                    $state !== null ? (HorarioHelper::horasEnteras()[$state] ?? $state) : null),


                // Mostrar el modo guardado (si falta, hereda)
                Tables\Columns\TextColumn::make('modo')
                    ->label('Modo')->badge()
                    ->formatStateUsing(fn($state, $record) =>
                    $state ?? ($record->consultorio->modo_defecto ?? 'horario'))
                    ->color(fn($state, $record) => ($state ?? ($record->consultorio->modo_defecto ?? 'horario')) === 'horario'
                        ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('slot_minutos')->label('Slot (min)')->toggleable(),
                Tables\Columns\TextColumn::make('cupos_por_hora')->label('Cupos/hora')->toggleable(),
                Tables\Columns\IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('agregarTurnos')
                    ->label('Agregar turnos')
                    ->icon('heroicon-o-plus')
                    ->form(fn() => $this->bulkSchema())
                    ->action(function (array $data) {
                        $owner = $this->getOwnerRecord();
                        $dias  = $data['dias'] ?? [];

                        if (empty($dias)) {
                            Notification::make()->title('Selecciona al menos un día.')->danger()->send();
                            return;
                        }
                        if ($data['hora_inicio'] >= $data['hora_fin']) {
                            Notification::make()->title('La hora fin debe ser mayor que la hora inicio.')->danger()->send();
                            return;
                        }

                        $modoConsultorio = $owner->modo_defecto ?? 'horario';

                        DB::transaction(function () use ($owner, $dias, $data, $modoConsultorio) {
                            foreach ($dias as $dia) {
                                $exists = $owner->turnos()
                                    ->where('dia_semana', $dia)
                                    ->where('hora_inicio', $data['hora_inicio'])
                                    ->where('hora_fin', $data['hora_fin'])
                                    ->exists();

                                if ($exists) continue;

                                $owner->turnos()->create([
                                    'dia_semana'     => $dia,
                                    'hora_inicio'    => $data['hora_inicio'],
                                    'hora_fin'       => $data['hora_fin'],
                                    'modo'           => $modoConsultorio, // ✅ guardamos el modo efectivo
                                    'slot_minutos'   => $modoConsultorio === 'horario' ? ($data['slot_minutos'] ?? 30) : null,
                                    'cupos_por_hora' => $modoConsultorio === 'cupos'   ? ($data['cupos_por_hora'] ?? 6) : null,
                                    'activo'         => (bool) ($data['activo'] ?? true),
                                ]);
                            }
                        });

                        Notification::make()->title('Turnos creados correctamente')->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        if (($data['hora_inicio'] ?? '00:00') >= ($data['hora_fin'] ?? '00:00')) {
                            throw new \Filament\Support\Exceptions\Halt();
                        }
                        // No tocamos 'modo': ya está guardado
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
